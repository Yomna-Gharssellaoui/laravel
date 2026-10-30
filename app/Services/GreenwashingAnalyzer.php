<?php

namespace App\Services;

use App\Models\AIAnalysis;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Services\Ai\AiProvider;
use App\Services\Ml\MlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Scores environmental claims for greenwashing risk.
 *
 * First choice: the local ML model of module M5 (ml/modules/m5_greenwashing.py), combined with
 * the evidence stored in the database. Otherwise, with an AI key (Claude or Gemini, see AiProvider), the claim and its evidence
 * (certifications, footprint, compliance checks) are sent to the model, which returns a
 * structured JSON verdict. Without a key, or if the call fails, a transparent rule-based
 * heuristic is used and the analysis is flagged as is_demo.
 */
class GreenwashingAnalyzer
{
    public const HEURISTIC_MODEL = 'heuristique-locale-v1';

    public const ML_MODEL = 'ml-m5-greenwashing (scikit-learn)';

    /** Vague marketing terms that are not meaningful without proof. */
    private const VAGUE_TERMS = [
        'écologique', 'éco', 'vert', 'green', 'naturel', 'durable', 'responsable',
        'respectueux', 'propre', 'neutre en carbone', 'carbone neutre', 'zéro impact',
        '100%', '100 %', 'sans impact', 'planète', 'biodégradable',
    ];

    private const RESPONSE_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'greenwashing_score' => [
                'type' => 'integer',
                'description' => '0 = claim fully substantiated, 100 = almost certainly greenwashing',
            ],
            'credibility_rating' => ['type' => 'string', 'enum' => ['élevée', 'moyenne', 'faible']],
            'summary' => ['type' => 'string', 'description' => 'Deux à quatre phrases en français'],
            'red_flags' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
        'required' => ['greenwashing_score', 'credibility_rating', 'summary', 'red_flags'],
        'additionalProperties' => false,
    ];

    private const SYSTEM_PROMPT = <<<'PROMPT'
Tu es un auditeur spécialisé dans la détection du greenwashing sur des produits alimentaires,
selon les principes de la directive européenne sur les allégations environnementales (Green Claims).
On te donne une ou plusieurs déclarations environnementales d'un produit ainsi que les preuves
disponibles dans la base (certifications, empreinte mesurée, vérifications de conformité).

Évalue si les déclarations sont étayées par ces preuves. Sont des signaux d'alerte : termes vagues
sans preuve ("écologique", "naturel", "neutre en carbone"), absence de certification correspondante,
certification expirée ou rejetée, empreinte élevée contredisant la déclaration, vérification échouée,
absence de document source. Une déclaration précise, chiffrée et couverte par une certification
vérifiée a un score faible.

Réponds en français. Le résumé doit être factuel et citer les preuves utilisées. Ne présente jamais
le résultat comme une preuve de fraude : c'est une aide à la décision pour un administrateur.
PROMPT;

    public function analyzeClaim(EnvironmentalClaim $claim): AIAnalysis
    {
        if ($claim->product_id === null) {
            throw new \InvalidArgumentException('A claim must be linked to a product to be analysed.');
        }

        $claim->loadMissing(['product.certificates', 'product.environmentalFootprint', 'complianceChecks']);

        $analysis = $this->run($claim->product, collect([$claim]), 'déclaration', $claim);

        // The claim's confidence is the inverse of its greenwashing risk
        $claim->updateQuietly(['confidence_score' => 100 - $analysis->greenwashing_score]);

        return $analysis;
    }

    public function analyzeProduct(Product $product): AIAnalysis
    {
        $product->loadMissing(['certificates', 'environmentalFootprint', 'environmentalClaims.complianceChecks']);

        return $this->run($product, $product->environmentalClaims, 'produit');
    }

    /**
     * @param  Collection<int, EnvironmentalClaim>  $claims
     */
    private function run(Product $product, $claims, string $type, ?EnvironmentalClaim $claim = null): AIAnalysis
    {
        $result = null;
        $model = self::HEURISTIC_MODEL;

        // 1. Local ML model (module M5), 2. optional LLM provider, 3. rule-based heuristic
        if ($ml = $this->machineLearning($product, $claims)) {
            [$result, $model] = [$ml, self::ML_MODEL];
        } elseif ($llm = $this->ai->current()) {
            try {
                $result = $this->validResult($llm->json(self::SYSTEM_PROMPT, $this->buildContext($product, $claims), self::RESPONSE_SCHEMA));
                $model = $llm->name();
            } catch (Throwable $e) {
                Log::warning('Greenwashing analysis via '.$llm->name().' failed, using heuristic', ['error' => $e->getMessage()]);
            }
        }

        $result ??= $this->heuristic($product, $claims);

        return AIAnalysis::create([
            'product_id' => $product?->id,
            'environmental_claim_id' => $claim?->id,
            'analysis_type' => $type,
            'greenwashing_score' => max(0, min(100, (int) $result['greenwashing_score'])),
            'credibility_rating' => $result['credibility_rating'],
            'ai_summary' => $result['summary'],
            'red_flags' => array_values($result['red_flags']),
            'model_used' => $model,
            'is_demo' => $model === self::HEURISTIC_MODEL,
        ]);
    }

    public function __construct(private AiProvider $ai, private MlService $ml) {}

    /**
     * ML score on the claim text (TF-IDF + logistic regression), combined with the evidence
     * found in the database (certifications, compliance checks, footprint) by the heuristic.
     */
    private function machineLearning(Product $product, $claims): ?array
    {
        $text = $claims->map(fn ($c) => trim($c->claim_type.'. '.$c->title.'. '.$c->description, '. '))->implode(' ');
        $prediction = $text !== '' ? $this->ml->greenwashing($text) : null;

        if ($prediction === null) {
            return null;
        }

        $evidence = $this->heuristic($product, $claims);
        $score = (int) round(0.6 * $prediction['probability'] + 0.4 * $evidence['greenwashing_score']);

        $flags = array_map(fn ($term) => "Expression à risque repérée par le modèle : « {$term} »", $prediction['risky_terms']);
        $flags = array_merge($flags, array_values(array_filter(
            $evidence['red_flags'],
            fn ($flag) => ! str_starts_with($flag, 'Termes vagues')
        )));

        $summary = "Modèle ML (TF-IDF + régression logistique) : {$prediction['probability']} % de probabilité de greenwashing d’après le texte";
        $summary .= $prediction['reassuring_terms'] !== []
            ? ' (éléments vérifiables : '.implode(', ', $prediction['reassuring_terms']).'). '
            : '. ';
        $summary .= 'Score final combiné avec les preuves en base (certifications, vérifications, empreinte) : '.$score.'/100.';

        return [
            'greenwashing_score' => $score,
            'credibility_rating' => $score < 35 ? 'élevée' : ($score < 65 ? 'moyenne' : 'faible'),
            'summary' => $summary,
            'red_flags' => $flags,
        ];
    }

    public function aiEnabled(): bool
    {
        return $this->ai->enabled();
    }

    public function modelName(): ?string
    {
        return $this->ai->current()?->name();
    }

    /**
     * Guard against a model answer that does not follow the schema.
     */
    private function validResult(array $data): array
    {
        if (! isset($data['greenwashing_score'], $data['credibility_rating'], $data['summary']) || ! is_numeric($data['greenwashing_score'])) {
            throw new \UnexpectedValueException('Incomplete AI answer: '.json_encode($data));
        }

        $data['red_flags'] = array_values(array_filter((array) ($data['red_flags'] ?? []), 'is_string'));
        if (! in_array($data['credibility_rating'], ['élevée', 'moyenne', 'faible'], true)) {
            $data['credibility_rating'] = 'moyenne';
        }

        return $data;
    }

    private function buildContext(?Product $product, $claims): string
    {
        $lines = ['# Produit', $product ? "{$product->name} (catégorie : ".($product->category ?? '—').', origine : '.($product->origin ?? '—').')' : 'Aucun produit lié', ''];

        $lines[] = '# Déclarations environnementales';
        foreach ($claims as $c) {
            $lines[] = "- [{$c->claim_type}] {$c->title} : ".($c->description ?: '(pas de description)');
            $lines[] = '  Document source : '.($c->document_path ? 'fichier fourni' : ($c->source_document ?: 'aucun'));
            foreach ($c->complianceChecks as $check) {
                $lines[] = "  Vérification {$check->check_type} : {$check->status}".($check->result_summary ? " — {$check->result_summary}" : '');
            }
        }
        if ($claims->isEmpty()) {
            $lines[] = '(aucune déclaration)';
        }

        $lines[] = '';
        $lines[] = '# Certifications';
        foreach ($product?->certificates ?? [] as $cert) {
            $lines[] = "- {$cert->certificate_type} ({$cert->issuer}) : statut {$cert->status}, expire le ".($cert->expiry_date?->format('Y-m-d') ?? '—');
        }
        if (! $product || $product->certificates->isEmpty()) {
            $lines[] = '(aucune certification)';
        }

        $lines[] = '';
        $lines[] = '# Empreinte mesurée';
        $fp = $product?->environmentalFootprint;
        $lines[] = $fp
            ? "CO2 : {$fp->co2_emissions} kg CO2e/kg, eau : {$fp->water_usage} L/kg, sols : {$fp->land_usage} m²/kg, score calculé : {$fp->ai_score}/100"
            : '(aucune empreinte)';

        return implode("\n", $lines);
    }

    /**
     * Explainable fallback: every point added to the score comes with a red flag.
     */
    private function heuristic(?Product $product, $claims): array
    {
        $score = 15;
        $flags = [];

        $text = Str::lower($claims->map(fn ($c) => $c->claim_type.' '.$c->title.' '.$c->description)->implode(' '));
        // Whole words only: "éco" must not match inside "récolté"
        $found = array_values(array_filter(
            self::VAGUE_TERMS,
            fn ($term) => preg_match('/(?<![\pL\pN])'.preg_quote($term, '/').'(?![\pL\pN])/u', $text) === 1
        ));
        if ($found !== []) {
            $score += min(36, 12 * count($found));
            $flags[] = 'Termes vagues sans preuve chiffrée : '.implode(', ', array_slice($found, 0, 5));
        }

        $verifiedCerts = $product?->certificates->where('status', 'verified') ?? collect();
        if ($verifiedCerts->isEmpty()) {
            $score += 20;
            $flags[] = 'Aucune certification vérifiée pour ce produit';
        }
        if ($product?->certificates->whereIn('status', ['expired', 'rejected'])->isNotEmpty()) {
            $score += 10;
            $flags[] = 'Certification expirée ou rejetée';
        }

        if ($claims->isNotEmpty() && $claims->every(fn ($c) => ! $c->document_path && ! $c->source_document)) {
            $score += 15;
            $flags[] = 'Aucun document source fourni';
        }

        $checks = $claims->flatMap->complianceChecks;
        if ($checks->where('status', 'failed')->isNotEmpty()) {
            $score += 20;
            $flags[] = 'Au moins une vérification de conformité a échoué';
        }
        if ($checks->isNotEmpty() && $checks->every(fn ($c) => $c->status === 'passed')) {
            $score -= 25;
        }

        $fp = $product?->environmentalFootprint;
        if ($fp && $fp->ai_score !== null && $fp->ai_score < 40) {
            $score += 10;
            $flags[] = "Empreinte mesurée défavorable (score {$fp->ai_score}/100)";
        }

        $score = max(0, min(100, $score));

        return [
            'greenwashing_score' => $score,
            'credibility_rating' => $score < 35 ? 'élevée' : ($score < 65 ? 'moyenne' : 'faible'),
            'summary' => 'Analyse heuristique locale (sans IA) : '.($flags === []
                ? 'les déclarations sont couvertes par des preuves vérifiées.'
                : count($flags).' signal(aux) d’alerte relevé(s). Configurez GEMINI_API_KEY ou ANTHROPIC_API_KEY pour une analyse par IA.'),
            'red_flags' => $flags,
        ];
    }
}

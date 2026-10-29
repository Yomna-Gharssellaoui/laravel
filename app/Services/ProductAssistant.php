<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Ai\AiProvider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Answers consumers' questions about a product, grounded only in the
 * traceability data stored for it (Claude or Gemini; factual fallback without a key).
 */
class ProductAssistant
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Tu es l'assistant de Trace Verte, une plateforme de traçabilité alimentaire.
Tu réponds aux questions d'un consommateur sur UN produit, uniquement à partir de la fiche fournie
(producteur, transformation, parcours logistique, certifications, empreinte, déclarations
environnementales, vérifications, alertes, avis). Si l'information n'est pas dans la fiche, dis-le
simplement au lieu de supposer. Ne donne pas de conseil médical : pour une allergie, renvoie à la
liste des ingrédients et recommande de vérifier l'étiquette.
Réponds en français, en 2 à 5 phrases, sans titre ni liste à puces sauf si on te le demande.
PROMPT;

    public function __construct(private AiProvider $ai) {}

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    /**
     * @return array{answer: string, ai: bool}
     */
    public function ask(Product $product, string $question): array
    {
        if ($llm = $this->ai->current()) {
            try {
                $prompt = "<fiche_produit>\n{$this->productSheet($product)}\n</fiche_produit>\n\nQuestion du consommateur : {$question}";

                return ['answer' => $llm->text(self::SYSTEM_PROMPT, $prompt), 'ai' => true];
            } catch (Throwable $e) {
                Log::warning('Product assistant via '.$llm->name().' failed', ['error' => $e->getMessage()]);
            }
        }

        return [
            'answer' => 'L’assistant IA n’est pas disponible pour le moment. Voici ce que nous savons du produit : '.$this->shortSummary($product),
            'ai' => false,
        ];
    }

    public function productSheet(Product $product): string
    {
        $product->loadMissing([
            'producer', 'transformer', 'environmentalFootprint', 'certificates',
            'supplyChainTraces.distributor', 'environmentalClaims.complianceChecks',
            'openAlerts', 'approvedReviews',
        ]);

        $fp = $product->environmentalFootprint;
        $lines = [
            "Nom : {$product->name}",
            'Catégorie : '.($product->category ?? '—').' · Origine : '.($product->origin ?? '—'),
            'Ingrédients : '.($product->ingredients ?? 'non renseignés'),
            'Producteur : '.($product->producer ? "{$product->producer->company_name} ({$product->producer->location}, méthode {$product->producer->farming_method})" : '—'),
            'Transformateur : '.($product->transformer ? "{$product->transformer->company_name} ({$product->transformer->transformation_type})" : 'aucun'),
            'Empreinte : '.($fp ? "CO2 {$fp->co2_emissions} kg CO2e/kg, eau {$fp->water_usage} L/kg, sols {$fp->land_usage} m²/kg, score {$fp->ai_score}/100" : 'non mesurée'),
        ];

        $lines[] = 'Certifications : '.($product->certificates->map(fn ($c) => "{$c->certificate_type} ({$c->status}".($c->expiry_date ? ', expire le '.$c->expiry_date->format('d/m/Y') : '').')')->implode(' ; ') ?: 'aucune');

        foreach ($product->supplyChainTraces as $trace) {
            $steps = collect($trace->path_history ?? [])->map(fn ($s) => ($s['stage'] ?? '?').' à '.($s['location'] ?? '?'))->implode(' → ');
            $lines[] = 'Parcours ('.($trace->distributor?->company_name ?? 'distributeur inconnu').", {$trace->status}, {$trace->total_distance_km} km) : ".($steps ?: $trace->current_stage);
        }

        foreach ($product->environmentalClaims as $claim) {
            $checks = $claim->complianceChecks->map(fn ($c) => "{$c->check_type}: {$c->status}")->implode(', ');
            $lines[] = "Déclaration « {$claim->title} » ({$claim->status})".($checks ? " — vérifications : {$checks}" : '');
        }

        $lines[] = 'Alertes ouvertes : '.($product->openAlerts->map(fn ($a) => "{$a->title} ({$a->severity})")->implode(' ; ') ?: 'aucune');
        $avg = $product->approvedReviews->avg('rating');
        $lines[] = 'Avis : '.($avg !== null ? round($avg, 1).'/5 sur '.$product->approvedReviews->count().' avis' : 'aucun');

        return implode("\n", $lines);
    }

    private function shortSummary(Product $product): string
    {
        $product->loadMissing(['producer', 'environmentalFootprint', 'certificates']);

        $parts = [
            'produit par '.($product->producer?->company_name ?? 'un producteur non renseigné'),
            'origine '.($product->origin ?? 'non renseignée'),
        ];
        if ($product->environmentalFootprint) {
            $parts[] = 'score environnemental '.$product->environmentalFootprint->ai_score.'/100';
        }
        $parts[] = 'ingrédients : '.($product->ingredients ?: 'non renseignés');
        $certs = $product->certificates->where('status', 'verified')->pluck('certificate_type');
        $parts[] = $certs->isNotEmpty() ? 'certifications vérifiées : '.$certs->implode(', ') : 'aucune certification vérifiée';

        return ucfirst(implode(', ', $parts)).'.';
    }
}

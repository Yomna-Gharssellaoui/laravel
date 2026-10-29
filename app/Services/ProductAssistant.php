<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Answers consumers' questions about a product, grounded only in the
 * traceability data stored for it (Claude API; factual fallback without a key).
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

    public function enabled(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    /**
     * @return array{answer: string, ai: bool}
     */
    public function ask(Product $product, string $question): array
    {
        $sheet = $this->productSheet($product);

        if ($this->enabled()) {
            try {
                return ['answer' => $this->askClaude($sheet, $question), 'ai' => true];
            } catch (Throwable $e) {
                Log::warning('Product assistant failed', ['error' => $e->getMessage()]);
            }
        }

        return [
            'answer' => 'L’assistant IA n’est pas disponible pour le moment. Voici ce que nous savons du produit : '.$this->shortSummary($product),
            'ai' => false,
        ];
    }

    private function askClaude(string $sheet, string $question): string
    {
        $client = new Client(apiKey: config('services.anthropic.api_key'));

        $message = $client->beta->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 2000,
            system: self::SYSTEM_PROMPT,
            messages: [
                ['role' => 'user', 'content' => "<fiche_produit>\n{$sheet}\n</fiche_produit>\n\nQuestion du consommateur : {$question}"],
            ],
            outputConfig: ['effort' => 'low'],
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );

        if ($message->stopReason === 'refusal') {
            throw new \RuntimeException('Claude declined the request');
        }

        $text = collect($message->content)
            ->filter(fn ($block) => $block->type === 'text')
            ->map(fn ($block) => $block->text)
            ->implode("\n");

        return trim($text) !== '' ? trim($text) : throw new \RuntimeException('Empty answer');
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

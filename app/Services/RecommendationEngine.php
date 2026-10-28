<?php

namespace App\Services;

use App\Models\Consumer;
use App\Models\PersonalRating;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Computes a 0-100 personalised score for every published product,
 * from the consumer's preferences and allergies and the product's evidence
 * (footprint, certifications, open alerts, reviews).
 */
class RecommendationEngine
{
    public const LOCAL_ORIGINS = ['france', 'tunisie'];

    public function recompute(Consumer $consumer): int
    {
        $products = Product::where('status', 'published')
            ->with(['environmentalFootprint', 'certificates', 'openAlerts', 'approvedReviews'])
            ->get();

        foreach ($products as $product) {
            [$score, $reasons] = $this->score($consumer, $product);

            PersonalRating::updateOrCreate(
                ['consumer_id' => $consumer->id, 'product_id' => $product->id],
                [
                    'personalized_score' => $score,
                    'reason' => implode(' ; ', $reasons),
                    'recommendation_reason' => $reasons === [] ? 'Score basé sur l’empreinte environnementale.' : Str::ucfirst(implode(', ', array_slice($reasons, 0, 3))).'.',
                ]
            );
        }

        // Products that are no longer published are no longer recommended
        PersonalRating::where('consumer_id', $consumer->id)
            ->whereNotIn('product_id', $products->pluck('id'))
            ->delete();

        return $products->count();
    }

    public function recomputeAll(): int
    {
        $count = 0;

        Consumer::query()->each(function (Consumer $consumer) use (&$count) {
            $this->recompute($consumer);
            $count++;
        });

        return $count;
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    public function score(Consumer $consumer, Product $product): array
    {
        $preferences = collect($consumer->preferences ?? []);
        $footprint = $product->environmentalFootprint;
        $reasons = [];

        // An allergen in the ingredients excludes the product whatever its other qualities
        $allergen = $this->matchingAllergen($consumer->allergies ?? [], $product->ingredients);
        if ($allergen !== null) {
            return [0, ["contient « {$allergen} » (allergie déclarée)"]];
        }

        $score = $footprint?->ai_score ?? 50;

        if ($preferences->contains('bio')) {
            $isOrganic = $product->certificates->contains(fn ($c) => $c->status === 'verified'
                && Str::contains(Str::lower($c->certificate_type), ['bio', 'ab', 'organic', 'ecocert']));
            $score += $isOrganic ? 15 : -5;
            $reasons[] = $isOrganic ? 'certification bio vérifiée' : 'pas de certification bio vérifiée';
        }

        if ($preferences->contains('local')) {
            $isLocal = Str::contains(Str::lower($product->origin ?? ''), self::LOCAL_ORIGINS);
            if ($isLocal) {
                $score += 10;
                $reasons[] = 'origine locale ('.$product->origin.')';
            }
        }

        if ($preferences->contains('faible_co2') && $footprint?->co2_emissions !== null) {
            $co2 = (float) $footprint->co2_emissions;
            if ($co2 < 5) {
                $score += 15;
                $reasons[] = "faible empreinte carbone ({$co2} kg CO₂e/kg)";
            } elseif ($co2 > 10) {
                $score -= 10;
                $reasons[] = "empreinte carbone élevée ({$co2} kg CO₂e/kg)";
            }
        }

        if ($preferences->contains('faible_eau') && $footprint?->water_usage !== null && (float) $footprint->water_usage < 100) {
            $score += 10;
            $reasons[] = 'faible consommation d’eau';
        }

        $seriousAlerts = $product->openAlerts->whereIn('severity', ['high', 'critical'])->count();
        if ($seriousAlerts > 0) {
            $score -= min(30, 10 * $seriousAlerts);
            $reasons[] = $seriousAlerts.' alerte(s) importante(s) en cours';
        }

        $average = $product->approvedReviews->avg('rating');
        if ($average !== null && $average >= 4) {
            $score += 5;
            $reasons[] = 'très bien noté ('.round($average, 1).'/5)';
        }

        return [max(0, min(100, (int) round($score))), $reasons];
    }

    private function matchingAllergen(array $allergies, ?string $ingredients): ?string
    {
        if ($ingredients === null || $allergies === []) {
            return null;
        }

        $haystack = Str::lower(Str::ascii($ingredients));

        return collect($allergies)->first(fn ($allergy) => $allergy !== '' && str_contains($haystack, Str::lower(Str::ascii($allergy))));
    }
}

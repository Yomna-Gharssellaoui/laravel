<?php

namespace App\Services\Ml;

use App\Models\Consumer;
use App\Models\EnvironmentalClaim;
use App\Models\EnvironmentalFootprint;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupplyChainTrace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Client of the local Python ML service (ml/service.py), one method per module model.
 *
 * Every method returns null when the service is not configured, stopped or failing:
 * pages then simply hide the prediction, and business features keep their rule-based logic.
 * Predictions are deterministic, so they are cached for a few minutes.
 */
class MlService
{
    private const DOWN_KEY = 'ml.service.down';

    public function enabled(): bool
    {
        return filled(config('services.ml.url'));
    }

    // ---- M1 Produits & Empreinte -------------------------------------------

    public function footprintEstimate(Product $product): ?array
    {
        $product->loadMissing(['producer', 'transformer', 'certificates', 'supplyChainTraces']);

        return $this->post('/m1/footprint', [
            'category' => $product->category,
            'farming_method' => $product->producer?->farming_method,
            'transformation_type' => $product->transformer?->transformation_type ?? 'aucune',
            'distance_km' => (float) ($product->supplyChainTraces->max('total_distance_km') ?? 0),
            'organic_certified' => $product->certificates->contains(fn ($c) => $c->status === 'verified' && $c->isOrganic()),
        ]);
    }

    // ---- M2 Producteurs & Usines -------------------------------------------

    public function producerProfile(Producer $producer): ?array
    {
        return $this->post('/m2/producer-profile', [
            'farming_method' => $producer->farming_method,
            'capacity_tonnes' => self::capacityInTonnes($producer->production_capacity),
            'n_certifications' => count($producer->certifications ?? []),
            'n_crops' => max(1, count($producer->crop_types ?? [])),
        ]);
    }

    /**
     * "120 tonnes/an" → 120, "2 000 tonnes/an" → 2000, "1,5 million L/an" → 1500, "800 kg" → 0.8
     */
    public static function capacityInTonnes(?string $capacity): float
    {
        if (blank($capacity)) {
            return 0;
        }

        $text = Str::lower(str_replace(["\u{202F}", "\u{00A0}"], ' ', $capacity));
        if (! preg_match('/(\d[\d\s]*(?:[.,]\d+)?)/', $text, $m)) {
            return 0;
        }

        $value = (float) str_replace([' ', ','], ['', '.'], $m[1]);
        if (str_contains($text, 'million')) {
            $value *= 1_000_000;
        } elseif (str_contains($text, 'mille')) {
            $value *= 1_000;
        }

        $isTonnes = preg_match('/\btonnes?\b|\bt\b/', $text);

        return round($isTonnes ? $value : $value / 1000, 2); // kg or litres → tonnes
    }

    // ---- M3 Logistique ------------------------------------------------------

    public function delayRisk(SupplyChainTrace $trace): ?array
    {
        if (! in_array($trace->status, ['in_transit', 'delayed'], true)) {
            return null;
        }

        $trace->loadMissing(['product', 'distributor']);
        $steps = collect($trace->path_history ?? []);
        $start = $steps->pluck('at')->filter()->sort()->first();

        return $this->post('/m3/delay-risk', [
            'distance_km' => (float) ($trace->total_distance_km ?? 0),
            'n_steps' => max(1, $steps->count()),
            'current_stage' => $trace->current_stage,
            'distributor_type' => $trace->distributor?->distributor_type,
            'category' => $trace->product?->category,
            'days_in_transit' => $start ? round(now()->diffInHours($start, true) / 24, 1) : 0,
            'month' => (int) now()->format('n'),
        ]);
    }

    // ---- M4 Certifications & Avis ------------------------------------------

    public function reviewAnalysis(Review|string $review): ?array
    {
        $text = is_string($review) ? $review : trim($review->title.'. '.$review->comment, '. ');

        return blank($text) ? null : $this->post('/m4/review', ['text' => $text]);
    }

    // ---- M5 Alertes & IA ----------------------------------------------------

    public function greenwashing(EnvironmentalClaim|string $claim): ?array
    {
        $text = is_string($claim) ? $claim : trim($claim->title.'. '.$claim->description, '. ');

        return blank($text) ? null : $this->post('/m5/greenwashing', ['text' => $text]);
    }

    public function footprintAnomaly(EnvironmentalFootprint $footprint): ?array
    {
        $footprint->loadMissing('product');

        return $this->post('/m5/footprint-anomaly', [
            'category' => $footprint->product?->category,
            'co2_emissions' => (float) $footprint->co2_emissions,
            'water_usage' => (float) $footprint->water_usage,
            'land_usage' => (float) $footprint->land_usage,
        ]);
    }

    // ---- M6 Consommateurs ---------------------------------------------------

    /**
     * @return array<string, array{rating: float, neighbours: int}>|null keyed by product SKU
     */
    public function recommendations(Consumer $consumer): ?array
    {
        $consumer->loadMissing('user.reviews.product');

        $ratings = $consumer->user?->reviews
            ->filter(fn ($r) => $r->product?->sku)
            ->mapWithKeys(fn ($r) => [$r->product->sku => (float) $r->rating])
            ->all() ?? [];

        $response = $this->post('/m6/recommendations', [
            'preferences' => array_values($consumer->preferences ?? []),
            'allergies' => array_values($consumer->allergies ?? []),
            'ratings' => (object) $ratings,
        ]);

        return $response['predictions'] ?? null;
    }

    // ---- Service ------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>|null description and metrics of every model
     */
    public function models(): ?array
    {
        return $this->request('get', '/models');
    }

    public function health(): ?array
    {
        return $this->request('get', '/health', cache: false);
    }

    private function post(string $path, array $payload): ?array
    {
        return $this->request('post', $path, $payload);
    }

    private function request(string $method, string $path, array $payload = [], bool $cache = true): ?array
    {
        if (! $this->enabled() || Cache::get(self::DOWN_KEY)) {
            return null;
        }

        $call = function () use ($method, $path, $payload) {
            try {
                $response = Http::baseUrl(rtrim(config('services.ml.url'), '/'))
                    ->connectTimeout(1)
                    ->timeout((int) config('services.ml.timeout', 5))
                    ->acceptJson()
                    ->{$method}($path, $payload);

                if ($response->failed()) {
                    Log::warning("ML service {$path} returned HTTP {$response->status()}", ['body' => Str::limit($response->body(), 300)]);

                    return null;
                }

                return $response->json();
            } catch (Throwable $e) {
                // Not running: stop trying for 30 s so pages do not wait on every call
                Cache::put(self::DOWN_KEY, true, 30);
                Log::info('ML service unreachable: '.$e->getMessage());

                return null;
            }
        };

        if (! $cache) {
            return $call();
        }

        $key = 'ml:'.md5($path.json_encode($payload));
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $result = $call();
        if ($result !== null) {
            Cache::put($key, $result, now()->addMinutes(10));
        }

        return $result;
    }
}

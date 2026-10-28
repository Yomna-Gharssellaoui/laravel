<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentalFootprint extends Model
{
    /** @use HasFactory<\Database\Factories\EnvironmentalFootprintFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'co2_emissions',
        'water_usage',
        'land_usage',
        'ai_score',
    ];

    protected function casts(): array
    {
        return [
            'co2_emissions' => 'decimal:2',
            'water_usage' => 'decimal:2',
            'land_usage' => 'decimal:2',
            'ai_score' => 'integer',
        ];
    }

    /**
     * Reference values above which an indicator scores 0 (per kg of product).
     * weight = share of the final score.
     */
    public const INDICATORS = [
        'co2_emissions' => ['max' => 20, 'weight' => 0.5, 'unit' => 'kg CO₂e/kg'],
        'water_usage' => ['max' => 500, 'weight' => 0.3, 'unit' => 'L/kg'],
        'land_usage' => ['max' => 10, 'weight' => 0.2, 'unit' => 'm²/kg'],
    ];

    protected static function booted(): void
    {
        // The score is always derived from the measured indicators, never typed in
        static::saving(function (EnvironmentalFootprint $footprint) {
            $footprint->ai_score = $footprint->computeScore();
        });
    }

    /**
     * Weighted 0-100 score: 100 = no impact, 0 = at or above the reference value.
     * Missing indicators are ignored and the remaining weights are renormalised.
     */
    public function computeScore(): ?int
    {
        $total = 0.0;
        $weights = 0.0;

        foreach (self::INDICATORS as $field => $ref) {
            $value = $this->getAttribute($field);

            if ($value === null || $value === '') {
                continue;
            }

            $subScore = max(0, 100 - ((float) $value / $ref['max']) * 100);
            $total += $subScore * $ref['weight'];
            $weights += $ref['weight'];
        }

        return $weights > 0 ? (int) round($total / $weights) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function indicatorLevel(): string
    {
        $score = $this->ai_score;

        if ($score === null) {
            return 'unknown';
        }

        return match (true) {
            $score >= 75 => 'excellent',
            $score >= 50 => 'good',
            $score >= 25 => 'moderate',
            default => 'poor',
        };
    }
}

<?php

namespace App\Models;

use Database\Factories\SupplyChainTraceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyChainTrace extends Model
{
    /** @use HasFactory<SupplyChainTraceFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'distributor_id',
        'current_stage',
        'current_location_lat',
        'current_location_lon',
        'path_history',
        'status',
        'total_distance_km',
    ];

    public const STATUSES = ['in_transit', 'delivered', 'delayed', 'completed'];

    public const STAGES = ['production', 'transformation', 'stockage', 'transport', 'distribution', 'vente'];

    protected function casts(): array
    {
        return [
            'path_history' => 'array',
            'current_location_lat' => 'decimal:7',
            'current_location_lon' => 'decimal:7',
            'total_distance_km' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    /**
     * Sum of the great-circle distances between consecutive steps that have coordinates.
     */
    public static function distanceFromSteps(array $steps): ?float
    {
        $points = array_values(array_filter($steps, fn ($s) => isset($s['lat'], $s['lon']) && $s['lat'] !== null && $s['lon'] !== null));

        if (count($points) < 2) {
            return null;
        }

        $km = 0.0;
        for ($i = 1; $i < count($points); $i++) {
            $km += self::haversine((float) $points[$i - 1]['lat'], (float) $points[$i - 1]['lon'], (float) $points[$i]['lat'], (float) $points[$i]['lon']);
        }

        return round($km, 2);
    }

    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

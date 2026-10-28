<?php

namespace App\Models;

use Database\Factories\DistributorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distributor extends Model
{
    /** @use HasFactory<DistributorFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'distributor_type',
        'location',
        'latitude',
        'longitude',
        'coverage_area',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplyChainTraces(): HasMany
    {
        return $this->hasMany(SupplyChainTrace::class);
    }
}

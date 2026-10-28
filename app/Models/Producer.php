<?php

namespace App\Models;

use Database\Factories\ProducerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producer extends Model
{
    /** @use HasFactory<ProducerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'location',
        'latitude',
        'longitude',
        'farming_method',
        'crop_types',
        'production_capacity',
        'certifications',
    ];

    protected function casts(): array
    {
        return [
            'crop_types' => 'array',
            'certifications' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

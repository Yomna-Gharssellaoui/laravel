<?php

namespace App\Models;

use Database\Factories\TransformerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transformer extends Model
{
    /** @use HasFactory<TransformerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'location',
        'latitude',
        'longitude',
        'transformation_type',
        'process_description',
        'production_capacity',
        'certifications',
    ];

    protected function casts(): array
    {
        return [
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

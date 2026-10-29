<?php

namespace App\Models;

use Database\Factories\ConsumerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consumer extends Model
{
    /** @use HasFactory<ConsumerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferences',
        'sustainability_level',
        'dietary_restrictions',
        'allergies',
        'budget_range',
    ];

    /** Preferences understood by the recommendation engine. */
    public const PREFERENCES = [
        'bio' => 'Bio / certifié',
        'local' => 'Local',
        'faible_co2' => 'Faible CO₂',
        'faible_eau' => 'Faible consommation d’eau',
    ];

    public const LEVELS = ['débutant', 'intermédiaire', 'engagé', 'expert'];

    public const BUDGETS = ['éco', 'moyen', 'premium'];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
            'dietary_restrictions' => 'array',
            'allergies' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalRatings(): HasMany
    {
        return $this->hasMany(PersonalRating::class);
    }
}

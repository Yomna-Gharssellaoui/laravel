<?php

namespace App\Models;

use Database\Factories\PersonalRatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRating extends Model
{
    /** @use HasFactory<PersonalRatingFactory> */
    use HasFactory;

    protected $fillable = [
        'consumer_id',
        'product_id',
        'personalized_score',
        'reason',
        'recommendation_reason',
    ];

    protected function casts(): array
    {
        return [
            'personalized_score' => 'integer',
        ];
    }

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

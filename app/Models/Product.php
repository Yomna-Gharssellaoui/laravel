<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'barcode',
        'sku',
        'category',
        'origin',
        'ingredients',
        'producer_id',
        'transformer_id',
        'status',
        'image_path',
    ];

    public const STATUSES = ['draft', 'published', 'archived'];

    /**
     * Products a back-office user is allowed to see.
     * Admin: everything. Producer / transformer: their own products.
     * Distributor: products they carry in a supply-chain trace.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'admin' => $query,
            'producer' => $query->where('producer_id', $user->producer?->id ?? 0),
            'transformer' => $query->where('transformer_id', $user->transformer?->id ?? 0),
            'distributor' => $query->whereHas('supplyChainTraces', fn ($q) => $q->where('distributor_id', $user->distributor?->id ?? 0)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function isOwnedBy(User $user): bool
    {
        return match ($user->role) {
            'admin' => true,
            'producer' => $user->producer !== null && $this->producer_id === $user->producer->id,
            'transformer' => $user->transformer !== null && $this->transformer_id === $user->transformer->id,
            default => false,
        };
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Whether the product has what it needs to be shown publicly.
     */
    public function missingForPublication(): array
    {
        $missing = [];

        if (! $this->environmentalFootprint()->exists()) {
            $missing[] = 'une empreinte environnementale';
        }

        if (! $this->certificates()->whereIn('status', ['verified', 'pending'])->exists()) {
            $missing[] = 'au moins une certification';
        }

        return $missing;
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function transformer(): BelongsTo
    {
        return $this->belongsTo(Transformer::class);
    }

    public function environmentalFootprint(): HasOne
    {
        return $this->hasOne(EnvironmentalFootprint::class);
    }

    public function supplyChainTraces(): HasMany
    {
        return $this->hasMany(SupplyChainTrace::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AIAnalysis::class);
    }

    public function personalRatings(): HasMany
    {
        return $this->hasMany(PersonalRating::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function openAlerts(): HasMany
    {
        return $this->hasMany(Alert::class)->whereIn('status', ['open', 'investigating']);
    }

    public function averageRating(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function environmentalClaims(): HasMany
    {
        return $this->hasMany(EnvironmentalClaim::class);
    }

    public function complianceChecks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }
}

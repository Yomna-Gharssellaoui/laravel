<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class EnvironmentalClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'claim_type',
        'title',
        'description',
        'source_document',
        'status',
        'confidence_score',
        'document_path',
    ];

    public const STATUSES = ['pending', 'verified', 'rejected'];

    public const TYPES = ['100% écologique', 'Produit local', 'Sans pesticide', 'Biodégradable', 'Fabrication responsable', 'Neutre en carbone', 'Agriculture biologique'];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function complianceChecks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AIAnalysis::class);
    }

    public function documentUrl(): ?string
    {
        return $this->document_path ? Storage::disk('public')->url($this->document_path) : null;
    }
}

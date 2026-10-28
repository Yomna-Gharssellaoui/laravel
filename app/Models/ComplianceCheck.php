<?php

namespace App\Models;

use App\Observers\ComplianceCheckObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ComplianceCheckObserver::class)]
class ComplianceCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'environmental_claim_id',
        'product_id',
        'check_type',
        'status',
        'result_summary',
        'notes',
        'checked_at',
    ];

    public const STATUSES = ['pending', 'passed', 'failed', 'needs_review'];

    public const TYPES = ['document_review', 'site_audit', 'lab_test', 'certificate_check'];

    protected function casts(): array
    {
        return [
            'checked_at' => 'date',
        ];
    }

    public function environmentalClaim(): BelongsTo
    {
        return $this->belongsTo(EnvironmentalClaim::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}

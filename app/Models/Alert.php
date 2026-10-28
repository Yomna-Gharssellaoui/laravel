<?php

namespace App\Models;

use App\Observers\AlertObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(AlertObserver::class)]
class Alert extends Model
{
    /** @use HasFactory<\Database\Factories\AlertFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'compliance_check_id',
        'alert_type',
        'severity',
        'title',
        'description',
        'detected_at',
        'status',
        'resolution',
    ];

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    public const STATUSES = ['open', 'investigating', 'resolved', 'dismissed'];

    public const OPEN_STATUSES = ['open', 'investigating'];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function complianceCheck(): BelongsTo
    {
        return $this->belongsTo(ComplianceCheck::class);
    }
}

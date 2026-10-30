<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'certificate_type',
        'issuer',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'status',
        'document_path',
    ];

    public const STATUSES = ['pending', 'verified', 'expired', 'rejected'];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Organic certification (Bio, AB, Ecocert, organic), matched on whole words:
     * "Label Rouge" must not count as "AB".
     */
    public function isOrganic(): bool
    {
        return preg_match('/\b(bio|ab|organic|ecocert)\b/iu', $this->certificate_type ?? '') === 1;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * Days left before expiry (negative once expired), null without an expiry date.
     */
    public function daysUntilExpiry(): ?int
    {
        return $this->expiry_date === null
            ? null
            : (int) now()->startOfDay()->diffInDays($this->expiry_date, false);
    }

    public function documentUrl(): ?string
    {
        return $this->document_path ? Storage::disk('public')->url($this->document_path) : null;
    }
}

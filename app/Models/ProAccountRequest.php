<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A consumer asks to become a producer, transformer or distributor. An admin approves it
 * (role changed + actor profile created from the request) or rejects it with a reason.
 */
class ProAccountRequest extends Model
{
    use HasFactory;

    public const ROLES = [
        'producer' => 'Producteur',
        'transformer' => 'Transformateur',
        'distributor' => 'Distributeur',
    ];

    /** Activity choices per role: farming method, transformation type, distributor type */
    public const ACTIVITIES = [
        'producer' => ['biologique', 'permaculture', 'raisonnée', 'conventionnelle'],
        'transformer' => ['pressage à froid', 'conserverie', 'meunerie', 'séchage solaire', 'laiterie', 'autre'],
        'distributor' => ['grossiste', 'retail', 'coopérative', 'e-commerce', 'transport frigorifique'],
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Acceptée',
        'rejected' => 'Refusée',
    ];

    protected $fillable = [
        'requested_role',
        'company_name',
        'location',
        'activity_type',
        'phone',
        'registration_number',
        'motivation',
        'document_path',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->requested_role] ?? $this->requested_role;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Gives the user the requested role and creates the matching actor profile from the request.
     */
    public function approve(User $admin, ?string $note = null): void
    {
        DB::transaction(function () use ($admin, $note) {
            $user = $this->user;
            $profile = [
                'company_name' => $this->company_name,
                'location' => $this->location,
            ];

            match ($this->requested_role) {
                'producer' => $user->producer()->firstOrCreate([], $profile + ['farming_method' => $this->activity_type]),
                'transformer' => $user->transformer()->firstOrCreate([], $profile + ['transformation_type' => $this->activity_type]),
                'distributor' => $user->distributor()->firstOrCreate([], $profile + ['distributor_type' => $this->activity_type]),
            };

            // role is not mass-assignable: only an admin decision changes it
            $user->role = $this->requested_role;
            $user->save();

            $this->decide('approved', $admin, $note);
        });
    }

    public function reject(User $admin, string $note): void
    {
        $this->decide('rejected', $admin, $note);
    }

    public function deleteDocument(): void
    {
        if ($this->document_path) {
            Storage::disk('local')->delete($this->document_path);
        }
    }

    private function decide(string $status, User $admin, ?string $note): void
    {
        $this->forceFill([
            'status' => $status,
            'admin_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }
}

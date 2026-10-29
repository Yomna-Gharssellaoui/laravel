<?php

namespace App\Observers;

use App\Models\Alert;
use App\Models\ComplianceCheck;

class ComplianceCheckObserver
{
    /**
     * Keep the claim status and the alerts in line with the audit result.
     */
    public function saved(ComplianceCheck $check): void
    {
        if (! $check->wasRecentlyCreated && ! $check->wasChanged('status')) {
            return;
        }

        $claim = $check->environmentalClaim;

        if ($check->status === 'failed') {
            $claim?->update(['status' => 'rejected']);

            if ($check->product_id && ! $check->alerts()->whereIn('status', Alert::OPEN_STATUSES)->exists()) {
                Alert::create([
                    'product_id' => $check->product_id,
                    'compliance_check_id' => $check->id,
                    'alert_type' => 'compliance',
                    'severity' => 'high',
                    'title' => 'Vérification échouée : '.($claim?->title ?? 'déclaration environnementale'),
                    'description' => $check->result_summary,
                    'detected_at' => now(),
                    'status' => 'open',
                ]);
            }
        }

        if ($check->status === 'passed' && $claim) {
            $allPassed = ! $claim->complianceChecks()->where('status', '!=', 'passed')->exists();

            if ($allPassed) {
                $claim->update(['status' => 'verified']);
            }
        }
    }
}

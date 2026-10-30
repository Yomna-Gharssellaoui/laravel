<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Certificate;
use App\Models\EnvironmentalFootprint;
use App\Models\SupplyChainTrace;
use App\Services\Ml\MlService;
use Illuminate\Console\Command;

class ScanAlerts extends Command
{
    protected $signature = 'traceverte:scan-alerts {--days=30 : Warn about certificates expiring within this many days}';

    protected $description = 'Expire outdated certificates and raise alerts (certificates, delays, high footprints, ML anomalies and delay risks)';

    public const CO2_THRESHOLD = 15;

    public const DELAY_RISK_THRESHOLD = 60;

    private int $created = 0;

    private MlService $ml;

    public function handle(MlService $ml): int
    {
        $this->ml = $ml;

        $today = now()->startOfDay();

        // 1. Certificates past their expiry date
        Certificate::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', $today)
            ->where('status', '!=', 'expired')
            ->each(function (Certificate $certificate) {
                $certificate->update(['status' => 'expired']);
                $this->raise($certificate->product_id, 'certificate_expired', 'high',
                    "Certification expirée : {$certificate->certificate_type}",
                    "Le certificat {$certificate->certificate_number} a expiré le {$certificate->expiry_date->format('d/m/Y')}.");
            });

        // 2. Verified certificates expiring soon
        Certificate::where('status', 'verified')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays((int) $this->option('days'))])
            ->each(function (Certificate $certificate) {
                $this->raise($certificate->product_id, 'certificate_expiring', 'low',
                    "Certification bientôt expirée : {$certificate->certificate_type}",
                    "Expire le {$certificate->expiry_date->format('d/m/Y')}. Pensez au renouvellement.");
            });

        // 3. Delayed shipments
        SupplyChainTrace::where('status', 'delayed')
            ->each(function (SupplyChainTrace $trace) {
                $this->raise($trace->product_id, 'logistics_delay', 'medium',
                    'Livraison en retard',
                    'Trace #'.$trace->id.' bloquée à l’étape « '.($trace->current_stage ?? 'inconnue').' ».');
            });

        // 4. Footprints above the CO2 threshold
        EnvironmentalFootprint::where('co2_emissions', '>', self::CO2_THRESHOLD)
            ->each(function (EnvironmentalFootprint $footprint) {
                $this->raise($footprint->product_id, 'high_footprint', 'medium',
                    'Empreinte carbone élevée',
                    "{$footprint->co2_emissions} kg CO₂e/kg (seuil : ".self::CO2_THRESHOLD.').');
            });

        // 5. Machine learning (local service): atypical footprints (M5) and likely delays (M3)
        if ($this->ml->enabled()) {
            EnvironmentalFootprint::with('product')->each(function (EnvironmentalFootprint $footprint) {
                $anomaly = $this->ml->footprintAnomaly($footprint);
                if ($anomaly['is_anomaly'] ?? false) {
                    $this->raise($footprint->product_id, 'ml_footprint_anomaly', 'medium',
                        'Empreinte atypique (détection ML)',
                        'Score d’anomalie '.$anomaly['score'].'/100 pour la catégorie « '.$footprint->product?->category.' » (Isolation Forest, module M5).');
                }
            });

            SupplyChainTrace::where('status', 'in_transit')->with(['product', 'distributor'])->each(function (SupplyChainTrace $trace) {
                $risk = $this->ml->delayRisk($trace);
                if (($risk['risk'] ?? 0) >= self::DELAY_RISK_THRESHOLD) {
                    $this->raise($trace->product_id, 'ml_delay_risk', 'low',
                        'Risque de retard élevé (prédiction ML)',
                        'Trace #'.$trace->id.' : '.$risk['risk'].' % de risque de retard (Gradient Boosting, module M3).');
                }
            });
        }

        $this->info("{$this->created} alerte(s) créée(s).");

        return self::SUCCESS;
    }

    /**
     * Create the alert unless the same one is already open for this product.
     */
    private function raise(?int $productId, string $type, string $severity, string $title, string $description): void
    {
        if ($productId === null) {
            return;
        }

        $exists = Alert::where('product_id', $productId)
            ->where('alert_type', $type)
            ->where('title', $title)
            ->whereIn('status', Alert::OPEN_STATUSES)
            ->exists();

        if ($exists) {
            return;
        }

        Alert::create([
            'product_id' => $productId,
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
            'detected_at' => now(),
            'status' => 'open',
        ]);

        $this->created++;
    }
}

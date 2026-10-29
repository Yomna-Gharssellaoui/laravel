<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIAnalysis;
use App\Models\Alert;
use App\Models\Certificate;
use App\Models\Consumer;
use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupplyChainTrace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Every figure is limited to what the current user is allowed to see
        $products = Product::visibleTo($user);
        $productIds = (clone $products)->pluck('id');

        $alerts = Alert::whereIn('product_id', $productIds);
        $certificates = Certificate::whereIn('product_id', $productIds);

        $stats = [
            'products' => (clone $products)->count(),
            'published_products' => (clone $products)->where('status', 'published')->count(),
            'open_alerts' => (clone $alerts)->whereIn('status', Alert::OPEN_STATUSES)->count(),
            'certificates' => (clone $certificates)->count(),
            'expiring_certificates' => (clone $certificates)->where('status', 'verified')
                ->whereBetween('expiry_date', [now(), now()->addDays(30)])->count(),
            'traces_in_transit' => SupplyChainTrace::whereIn('product_id', $productIds)->where('status', 'in_transit')->count(),
        ];

        if ($user->isAdmin()) {
            $stats += [
                'users' => User::count(),
                'consumers' => Consumer::count(),
                'pending_reviews' => Review::where('status', 'pending')->count(),
                'analyses' => AIAnalysis::count(),
            ];
        }

        $charts = [
            'Produits par statut' => $this->distribution((clone $products), 'status', Product::STATUSES),
            'Alertes ouvertes par sévérité' => $this->distribution((clone $alerts)->whereIn('status', Alert::OPEN_STATUSES), 'severity', Alert::SEVERITIES),
            'Score environnemental' => $this->footprintLevels($productIds),
            'Certifications par statut' => $this->distribution((clone $certificates), 'status', Certificate::STATUSES),
        ];

        $recentProducts = (clone $products)->with(['producer', 'environmentalFootprint'])->latest()->take(5)->get();

        $recentAlerts = Alert::with('product')
            ->whereIn('product_id', $productIds)
            ->whereIn('status', Alert::OPEN_STATUSES)
            ->latest('detected_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'charts', 'recentProducts', 'recentAlerts'));
    }

    /**
     * @return array<string, int> value => count, in the given order
     */
    private function distribution($query, string $column, array $values): array
    {
        $counts = $query->selectRaw("$column as value, count(*) as total")->groupBy($column)->pluck('total', 'value');

        return collect($values)->mapWithKeys(fn ($v) => [$v => (int) ($counts[$v] ?? 0)])->all();
    }

    private function footprintLevels($productIds): array
    {
        $levels = ['excellent' => 0, 'good' => 0, 'moderate' => 0, 'poor' => 0];

        EnvironmentalFootprint::whereIn('product_id', $productIds)->get()
            ->each(function (EnvironmentalFootprint $f) use (&$levels) {
                $level = $f->indicatorLevel();
                if (isset($levels[$level])) {
                    $levels[$level]++;
                }
            });

        return $levels;
    }
}

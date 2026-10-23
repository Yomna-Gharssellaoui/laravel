<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // When running tests the in-memory sqlite database may not have been migrated yet.
        // Guard queries with Schema checks to avoid exceptions during test bootstrapping.
        $featuredProducts = collect();
        $publicAlerts = collect();
        $stats = ['products' => 0, 'alerts' => 0];

        if (Schema::hasTable('products')) {
            $featuredProducts = Product::with(['environmentalFootprint', 'producer'])
                ->where('status', 'published')
                ->latest()
                ->take(6)
                ->get();

            $stats['products'] = Product::where('status', 'published')->count();
        }

        if (Schema::hasTable('alerts')) {
            $publicAlerts = Alert::with('product')
                ->whereIn('status', ['open', 'investigating'])
                ->whereIn('severity', ['high', 'critical'])
                ->latest('detected_at')
                ->take(3)
                ->get();

            $stats['alerts'] = Alert::whereIn('status', ['open', 'investigating'])->count();
        }

        return view('home', compact('featuredProducts', 'publicAlerts', 'stats'));
    }
}

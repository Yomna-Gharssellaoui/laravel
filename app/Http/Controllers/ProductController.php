<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['environmentalFootprint', 'producer', 'approvedReviews'])
            ->where('status', 'published')
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('barcode', 'like', '%'.$request->q.'%')
                        ->orWhere('origin', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->origin, fn ($q) => $q->where('origin', 'like', '%'.$request->origin.'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Product::where('status', 'published')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('frontend.products.index', compact('products', 'categories'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'published' || (auth()->check() && auth()->user()->canAccessBackOffice()), 404);

        $product->load([
            'producer',
            'transformer',
            'environmentalFootprint',
            'certificates',
            'approvedReviews.user',
            'openAlerts',
            'aiAnalyses' => fn ($q) => $q->latest()->limit(3),
            'supplyChainTraces.distributor',
            'environmentalClaims.complianceChecks',
            'complianceChecks.environmentalClaim',
        ]);

        return view('frontend.products.show', compact('product'));
    }

    public function trace(Product $product): View
    {
        abort_unless($product->status === 'published', 404);

        $product->load([
            'producer',
            'transformer',
            'supplyChainTraces.distributor',
            'certificates',
        ]);

        return view('frontend.products.trace', compact('product'));
    }

    /**
     * Downloadable QR code (SVG) of the traceability page, for labels and packaging.
     */
    public function qrCode(Product $product): Response
    {
        abort_unless($product->status === 'published', 404);

        return response($product->traceQrCodeSvg(600), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.Str::slug($product->name).'.svg"',
        ]);
    }
}

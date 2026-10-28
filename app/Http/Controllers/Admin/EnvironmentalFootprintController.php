<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnvironmentalFootprint;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnvironmentalFootprintController extends Controller
{
    public function index(Request $request): View
    {
        $items = EnvironmentalFootprint::with('product')
            ->when($request->q, function ($q) use ($request) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->q.'%'));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.environmental-footprints.index', compact('items'));
    }

    public function create(): View
    {
        $products = Product::whereDoesntHave('environmentalFootprint')
            ->orderBy('name')
            ->get();

        return view('admin.environmental-footprints.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $item = EnvironmentalFootprint::create($data);

        return redirect()
            ->route('admin.environmental-footprints.show', $item)
            ->with('success', 'Empreinte environnementale créée.');
    }

    public function show(EnvironmentalFootprint $environmental_footprint): View
    {
        $environmental_footprint->load('product');

        return view('admin.environmental-footprints.show', ['item' => $environmental_footprint]);
    }

    public function edit(EnvironmentalFootprint $environmental_footprint): View
    {
        $products = Product::where(function ($q) use ($environmental_footprint) {
            $q->whereDoesntHave('environmentalFootprint')
                ->orWhere('id', $environmental_footprint->product_id);
        })->orderBy('name')->get();

        return view('admin.environmental-footprints.edit', [
            'item' => $environmental_footprint,
            'products' => $products,
        ]);
    }

    public function update(Request $request, EnvironmentalFootprint $environmental_footprint): RedirectResponse
    {
        $environmental_footprint->update($this->validated($request, $environmental_footprint));

        return redirect()
            ->route('admin.environmental-footprints.show', $environmental_footprint)
            ->with('success', 'Empreinte mise à jour.');
    }

    public function destroy(EnvironmentalFootprint $environmental_footprint): RedirectResponse
    {
        $environmental_footprint->delete();

        return redirect()
            ->route('admin.environmental-footprints.index')
            ->with('success', 'Empreinte supprimée.');
    }

    private function validated(Request $request, ?EnvironmentalFootprint $item = null): array
    {
        return $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('environmental_footprints', 'product_id')->ignore($item),
            ],
            'co2_emissions' => ['nullable', 'numeric', 'min:0'],
            'water_usage' => ['nullable', 'numeric', 'min:0'],
            'land_usage' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}

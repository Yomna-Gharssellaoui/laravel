<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Transformer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $items = Product::visibleTo($request->user())
            ->with(['producer', 'transformer', 'environmentalFootprint'])
            ->when($request->q, fn ($q) => $q->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('barcode', 'like', '%'.$request->q.'%')
                    ->orWhere('sku', 'like', '%'.$request->q.'%');
            }))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.products.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $this->validated($request);
        $product = Product::create($data);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load([
            'producer', 'transformer', 'environmentalFootprint',
            'certificates', 'reviews.user', 'alerts', 'aiAnalyses',
            'supplyChainTraces.distributor',
        ]);

        $product->loadCount(['certificates', 'reviews', 'alerts', 'aiAnalyses', 'supplyChainTraces', 'personalRatings']);

        return view('admin.products.show', ['item' => $product]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.edit', ['item' => $product] + $this->formData($request));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update($this->validated($request, $product));

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produit supprimé (ainsi que son empreinte, ses traces, certifications, avis, alertes et analyses).');
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            // Producers and transformers can only attach products to their own company
            'producers' => $user->isAdmin()
                ? Producer::orderBy('company_name')->get()
                : Producer::whereKey($user->producer?->id ?? 0)->get(),
            'transformers' => Transformer::orderBy('company_name')->get(),
            'canChooseProducer' => $user->isAdmin(),
        ];
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($product)],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'category' => ['nullable', 'string', 'max:100'],
            'origin' => ['nullable', 'string', 'max:255'],
            'ingredients' => ['nullable', 'string'],
            'producer_id' => ['nullable', 'exists:producers,id'],
            'transformer_id' => ['nullable', 'exists:transformers,id'],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        // Ownership is enforced server-side, whatever the form sends
        if ($user->hasRole('producer')) {
            $data['producer_id'] = $user->producer->id;
        } elseif ($user->hasRole('transformer') && $product) {
            $data['producer_id'] = $product->producer_id;
            $data['transformer_id'] = $product->transformer_id;
        }

        // A product cannot be published without a footprint and a certification
        if ($data['status'] === 'published') {
            $missing = $product ? $product->missingForPublication() : ['une empreinte environnementale', 'au moins une certification'];

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Impossible de publier : il manque '.implode(' et ', $missing).'. Enregistrez le produit en brouillon, complétez-le, puis publiez-le.',
                ]);
            }
        }

        if ($request->hasFile('image')) {
            if ($product?->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image') && $product?->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = null;
        }

        unset($data['image'], $data['remove_image']);

        return $data;
    }
}

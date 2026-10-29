<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ParsesCsv;
use App\Http\Controllers\Controller;
use App\Models\Transformer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransformerController extends Controller
{
    use ParsesCsv;

    public function index(Request $request): View
    {
        $items = Transformer::with('user')
            ->withCount('products')
            ->when($request->q, fn ($q) => $q->where(fn ($query) => $query
                ->where('company_name', 'like', '%'.$request->q.'%')
                ->orWhere('location', 'like', '%'.$request->q.'%')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.transformers.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.transformers.create', [
            'users' => $this->availableUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Transformer::create($this->validated($request));

        return redirect()->route('admin.transformers.show', $item)
            ->with('success', 'Transformateur créé.');
    }

    public function show(Transformer $transformer): View
    {
        $transformer->load(['user', 'products']);

        return view('admin.transformers.show', ['item' => $transformer]);
    }

    public function edit(Transformer $transformer): View
    {
        return view('admin.transformers.edit', [
            'item' => $transformer,
            'users' => $this->availableUsers($transformer),
        ]);
    }

    public function update(Request $request, Transformer $transformer): RedirectResponse
    {
        $transformer->update($this->validated($request, $transformer));

        return redirect()->route('admin.transformers.show', $transformer)
            ->with('success', 'Transformateur mis à jour.');
    }

    public function destroy(Transformer $transformer): RedirectResponse
    {
        $transformer->delete();

        return redirect()->route('admin.transformers.index')
            ->with('success', 'Transformateur supprimé.');
    }

    /**
     * Accounts with the transformer role that are not yet linked to another company.
     */
    private function availableUsers(?Transformer $current = null)
    {
        return User::where('role', 'transformer')
            ->where(fn ($q) => $q->whereDoesntHave('transformer')->orWhere('id', $current?->user_id ?? 0))
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request, ?Transformer $transformer = null): array
    {
        $data = $request->validate([
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'transformer'),
                Rule::unique('transformers', 'user_id')->ignore($transformer),
            ],
            'company_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'transformation_type' => ['nullable', 'string', 'max:100'],
            'process_description' => ['nullable', 'string'],
            'production_capacity' => ['nullable', 'string', 'max:100'],
            'certifications' => ['nullable', 'string'],
        ]);

        $data['certifications'] = $this->csvToArray($data['certifications'] ?? null);

        return $data;
    }
}

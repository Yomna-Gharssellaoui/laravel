<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeClaimJob;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnvironmentalClaimController extends Controller
{
    public function index(Request $request): View
    {
        $items = EnvironmentalClaim::with('product')
            ->when($request->q, function ($query, $term) {
                $query->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('claim_type', 'like', '%'.$term.'%'));
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.environmental-claims.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.environmental-claims.create', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = EnvironmentalClaim::create($this->validated($request));

        // Every new claim is screened for greenwashing in the background
        AnalyzeClaimJob::dispatch($item);

        return redirect()->route('admin.environmental-claims.show', $item)
            ->with('success', 'Déclaration environnementale créée. L’analyse de greenwashing a été lancée.');
    }

    public function show(EnvironmentalClaim $environmental_claim): View
    {
        $environmental_claim->load(['product', 'complianceChecks', 'aiAnalyses' => fn ($q) => $q->latest()]);

        return view('admin.environmental-claims.show', ['item' => $environmental_claim]);
    }

    public function edit(EnvironmentalClaim $environmental_claim): View
    {
        return view('admin.environmental-claims.edit', [
            'item' => $environmental_claim,
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, EnvironmentalClaim $environmental_claim): RedirectResponse
    {
        $environmental_claim->update($this->validated($request, $environmental_claim));

        if ($environmental_claim->wasChanged(['title', 'description', 'claim_type', 'document_path', 'source_document', 'product_id'])) {
            AnalyzeClaimJob::dispatch($environmental_claim);
        }

        return redirect()->route('admin.environmental-claims.show', $environmental_claim)
            ->with('success', 'Déclaration environnementale mise à jour.');
    }

    public function destroy(EnvironmentalClaim $environmental_claim): RedirectResponse
    {
        if ($environmental_claim->document_path) {
            Storage::disk('public')->delete($environmental_claim->document_path);
        }

        $environmental_claim->delete();

        return redirect()->route('admin.environmental-claims.index')
            ->with('success', 'Déclaration environnementale supprimée.');
    }

    private function validated(Request $request, ?EnvironmentalClaim $claim = null): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'claim_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'source_document' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'status' => ['required', Rule::in(EnvironmentalClaim::STATUSES)],
        ]);

        if ($request->hasFile('document')) {
            if ($claim?->document_path) {
                Storage::disk('public')->delete($claim->document_path);
            }
            $data['document_path'] = $request->file('document')->store('claims', 'public');
        }

        unset($data['document']);

        return $data;
    }
}

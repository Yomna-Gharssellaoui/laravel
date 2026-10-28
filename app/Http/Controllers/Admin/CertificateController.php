<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);

        $items = Certificate::with('product')
            ->whereHas('product', fn ($p) => $p->visibleTo($request->user()))
            ->when($request->certificate_type, fn ($q) => $q->where('certificate_type', $request->certificate_type))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('issuer', 'like', '%'.$request->q.'%')
                        ->orWhere('certificate_number', 'like', '%'.$request->q.'%')
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->q.'%'));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.certificates.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Certificate::class);

        return view('admin.certificates.create', [
            'products' => $this->ownedProducts($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Certificate::class);

        $item = Certificate::create($this->validated($request));

        return redirect()->route('admin.certificates.show', $item)
            ->with('success', 'Certification enregistrée (statut non vérifié automatiquement).');
    }

    public function show(Certificate $certificate): View
    {
        $this->authorize('view', $certificate);

        $certificate->load('product');

        return view('admin.certificates.show', ['item' => $certificate]);
    }

    public function edit(Request $request, Certificate $certificate): View
    {
        $this->authorize('update', $certificate);

        return view('admin.certificates.edit', [
            'item' => $certificate,
            'products' => $this->ownedProducts($request),
        ]);
    }

    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        $this->authorize('update', $certificate);

        $certificate->update($this->validated($request, $certificate));

        return redirect()->route('admin.certificates.show', $certificate)
            ->with('success', 'Certification mise à jour.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $this->authorize('delete', $certificate);

        if ($certificate->document_path) {
            Storage::disk('public')->delete($certificate->document_path);
        }

        $certificate->delete();

        return redirect()->route('admin.certificates.index')
            ->with('success', 'Certification supprimée.');
    }

    private function ownedProducts(Request $request)
    {
        $user = $request->user();

        return Product::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where(fn ($q) => $q
                ->where('producer_id', $user->producer?->id ?? 0)
                ->orWhere('transformer_id', $user->transformer?->id ?? 0)))
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request, ?Certificate $certificate = null): array
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', Rule::in($this->ownedProducts($request)->pluck('id'))],
            'certificate_type' => ['required', 'string', 'max:100'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'certificate_number' => [
                'nullable', 'string', 'max:100',
                Rule::unique('certificates', 'certificate_number')
                    ->where(fn ($q) => $q->where('issuer', $request->input('issuer')))
                    ->ignore($certificate),
            ],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'status' => ['required', Rule::in(Certificate::STATUSES)],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'product_id.in' => 'Vous ne pouvez certifier que vos propres produits.',
            'certificate_number.unique' => 'Ce numéro existe déjà pour cet émetteur.',
        ]);

        // Only an administrator can validate or reject a certificate:
        // any change made by the owner sends it back to review
        if (! $user->isAdmin()) {
            $data['status'] = 'pending';
        }

        if ($request->hasFile('document')) {
            if ($certificate?->document_path) {
                Storage::disk('public')->delete($certificate->document_path);
            }
            $data['document_path'] = $request->file('document')->store('certificates', 'public');
        }

        unset($data['document']);

        return $data;
    }
}

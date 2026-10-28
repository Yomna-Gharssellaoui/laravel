<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorController extends Controller
{
    public function index(Request $request): View
    {
        $items = Distributor::with('user')
            ->withCount('supplyChainTraces')
            ->when($request->q, fn ($q) => $q->where(fn ($query) => $query
                ->where('company_name', 'like', '%'.$request->q.'%')
                ->orWhere('location', 'like', '%'.$request->q.'%')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.distributors.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.distributors.create', [
            'users' => $this->availableUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Distributor::create($this->validated($request));

        return redirect()->route('admin.distributors.show', $item)
            ->with('success', 'Distributeur créé.');
    }

    public function show(Distributor $distributor): View
    {
        $distributor->load(['user', 'supplyChainTraces.product']);

        return view('admin.distributors.show', ['item' => $distributor]);
    }

    public function edit(Distributor $distributor): View
    {
        return view('admin.distributors.edit', [
            'item' => $distributor,
            'users' => $this->availableUsers($distributor),
        ]);
    }

    public function update(Request $request, Distributor $distributor): RedirectResponse
    {
        $distributor->update($this->validated($request, $distributor));

        return redirect()->route('admin.distributors.show', $distributor)
            ->with('success', 'Distributeur mis à jour.');
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $distributor->delete();

        return redirect()->route('admin.distributors.index')
            ->with('success', 'Distributeur supprimé.');
    }

    /**
     * Accounts with the distributor role that are not yet linked to another company.
     */
    private function availableUsers(?Distributor $current = null)
    {
        return User::where('role', 'distributor')
            ->where(fn ($q) => $q->whereDoesntHave('distributor')->orWhere('id', $current?->user_id ?? 0))
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request, ?Distributor $distributor = null): array
    {
        return $request->validate([
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'distributor'),
                Rule::unique('distributors', 'user_id')->ignore($distributor),
            ],
            'company_name' => ['required', 'string', 'max:255'],
            'distributor_type' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'coverage_area' => ['nullable', 'string', 'max:100'],
        ]);
    }
}

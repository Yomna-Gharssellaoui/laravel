<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\SupplyChainTrace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplyChainTraceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupplyChainTrace::class);

        $items = $this->visible($request)
            ->with(['product', 'distributor'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, function ($q) use ($request) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->q.'%'));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.supply-chain-traces.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SupplyChainTrace::class);

        return view('admin.supply-chain-traces.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SupplyChainTrace::class);

        $item = SupplyChainTrace::create($this->validated($request));

        return redirect()->route('admin.supply-chain-traces.show', $item)
            ->with('success', 'Trace logistique créée.');
    }

    public function show(SupplyChainTrace $supply_chain_trace): View
    {
        $this->authorize('view', $supply_chain_trace);

        $supply_chain_trace->load(['product', 'distributor']);

        return view('admin.supply-chain-traces.show', ['item' => $supply_chain_trace]);
    }

    public function edit(Request $request, SupplyChainTrace $supply_chain_trace): View
    {
        $this->authorize('update', $supply_chain_trace);

        return view('admin.supply-chain-traces.edit', ['item' => $supply_chain_trace] + $this->formData($request));
    }

    public function update(Request $request, SupplyChainTrace $supply_chain_trace): RedirectResponse
    {
        $this->authorize('update', $supply_chain_trace);

        $supply_chain_trace->update($this->validated($request));

        return redirect()->route('admin.supply-chain-traces.show', $supply_chain_trace)
            ->with('success', 'Trace logistique mise à jour.');
    }

    public function destroy(SupplyChainTrace $supply_chain_trace): RedirectResponse
    {
        $this->authorize('delete', $supply_chain_trace);

        $supply_chain_trace->delete();

        return redirect()->route('admin.supply-chain-traces.index')
            ->with('success', 'Trace logistique supprimée.');
    }

    /**
     * Traces a user may see: distributors see the shipments they carry,
     * producers / transformers see the traces of their own products.
     */
    private function visible(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            'admin' => SupplyChainTrace::query(),
            'distributor' => SupplyChainTrace::where('distributor_id', $user->distributor?->id ?? 0),
            default => SupplyChainTrace::whereHas('product', fn ($p) => $p->visibleTo($user)),
        };
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            // A distributor can attach any product it carries; others only their own products
            'products' => $user->hasRole(['admin', 'distributor'])
                ? Product::orderBy('name')->get()
                : Product::visibleTo($user)->orderBy('name')->get(),
            'distributors' => $user->hasRole('distributor')
                ? Distributor::whereKey($user->distributor?->id ?? 0)->get()
                : Distributor::orderBy('company_name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        $user = $request->user();
        $allowed = $this->formData($request);

        $data = $request->validate([
            'product_id' => ['required', Rule::in($allowed['products']->pluck('id'))],
            'distributor_id' => ['nullable', Rule::in($allowed['distributors']->pluck('id'))],
            'status' => ['required', Rule::in(SupplyChainTrace::STATUSES)],
            'total_distance_km' => ['nullable', 'numeric', 'min:0'],
            'steps' => ['nullable', 'array'],
            'steps.*.stage' => ['required', 'string', 'max:100'],
            'steps.*.location' => ['nullable', 'string', 'max:255'],
            'steps.*.at' => ['nullable', 'date'],
            'steps.*.lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:steps.*.lon'],
            'steps.*.lon' => ['nullable', 'numeric', 'between:-180,180', 'required_with:steps.*.lat'],
        ], [
            'product_id.in' => 'Ce produit ne fait pas partie de vos produits.',
            'distributor_id.in' => 'Distributeur non autorisé.',
            'steps.*.stage.required' => 'Chaque étape doit avoir un nom.',
        ]);

        if ($user->hasRole('distributor')) {
            $data['distributor_id'] = $user->distributor->id;
        }

        $steps = collect($data['steps'] ?? [])
            ->map(fn ($s) => [
                'stage' => $s['stage'],
                'location' => $s['location'] ?? null,
                'at' => ! empty($s['at']) ? Carbon::parse($s['at'])->toIso8601String() : null,
                'lat' => isset($s['lat']) ? (float) $s['lat'] : null,
                'lon' => isset($s['lon']) ? (float) $s['lon'] : null,
            ])
            ->sortBy(fn ($s) => $s['at'] ?? '9999')
            ->values()
            ->all();

        $last = end($steps) ?: null;

        unset($data['steps']);

        $data['path_history'] = $steps ?: null;
        // The current position is the last step of the journey
        $data['current_stage'] = $last['stage'] ?? null;
        $data['current_location_lat'] = $last['lat'] ?? null;
        $data['current_location_lon'] = $last['lon'] ?? null;
        // Computed from the GPS points unless entered manually
        $data['total_distance_km'] = $data['total_distance_km'] ?? SupplyChainTrace::distanceFromSteps($steps);

        return $data;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ComplianceCheck;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $items = Alert::with('product')
            ->when($request->alert_type, fn ($q) => $q->where('alert_type', $request->alert_type))
            ->when($request->severity, fn ($q) => $q->where('severity', $request->severity))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->q, fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->latest('detected_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.alerts.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.alerts.create', [
            'products' => Product::orderBy('name')->get(),
            'complianceChecks' => ComplianceCheck::with('environmentalClaim')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Alert::create($this->validated($request));

        return redirect()->route('admin.alerts.show', $item)
            ->with('success', 'Alerte créée.');
    }

    public function show(Alert $alert): View
    {
        $alert->load(['product', 'complianceCheck.environmentalClaim']);

        return view('admin.alerts.show', ['item' => $alert]);
    }

    public function edit(Alert $alert): View
    {
        return view('admin.alerts.edit', [
            'item' => $alert,
            'products' => Product::orderBy('name')->get(),
            'complianceChecks' => ComplianceCheck::with('environmentalClaim')->latest()->get(),
        ]);
    }

    public function update(Request $request, Alert $alert): RedirectResponse
    {
        $alert->update($this->validated($request));

        return redirect()->route('admin.alerts.show', $alert)
            ->with('success', 'Alerte mise à jour.');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        $alert->delete();

        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerte supprimée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'compliance_check_id' => ['nullable', 'exists:compliance_checks,id'],
            'alert_type' => ['required', 'string', 'max:100'],
            'severity' => ['required', Rule::in(Alert::SEVERITIES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'detected_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Alert::STATUSES)],
            'resolution' => ['required_if:status,resolved,dismissed', 'nullable', 'string'],
        ], [
            'resolution.required_if' => 'Décrivez la résolution avant de clôturer l’alerte.',
        ]);

        $data['detected_at'] ??= now();

        return $data;
    }
}

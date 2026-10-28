<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplianceCheck;
use App\Models\EnvironmentalClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplianceCheckController extends Controller
{
    public function index(Request $request): View
    {
        $items = ComplianceCheck::with(['environmentalClaim', 'product'])
            ->when($request->q, function ($query, $term) {
                $query->where(fn ($q) => $q->where('check_type', 'like', '%'.$term.'%')
                    ->orWhereHas('environmentalClaim', fn ($c) => $c->where('title', 'like', '%'.$term.'%')));
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.compliance-checks.index', compact('items'));
    }

    public function create(Request $request): View
    {
        return view('admin.compliance-checks.create', [
            'environmentalClaims' => EnvironmentalClaim::with('product')->orderBy('title')->get(),
            'selectedClaim' => $request->integer('environmental_claim_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = ComplianceCheck::create($this->validated($request));

        return redirect()->route('admin.compliance-checks.show', $item)
            ->with('success', $this->message($item, 'Vérification de conformité créée.'));
    }

    public function show(ComplianceCheck $compliance_check): View
    {
        $compliance_check->load(['environmentalClaim', 'product', 'alerts']);

        return view('admin.compliance-checks.show', ['item' => $compliance_check]);
    }

    public function edit(ComplianceCheck $compliance_check): View
    {
        return view('admin.compliance-checks.edit', [
            'item' => $compliance_check,
            'environmentalClaims' => EnvironmentalClaim::with('product')->orderBy('title')->get(),
            'selectedClaim' => null,
        ]);
    }

    public function update(Request $request, ComplianceCheck $compliance_check): RedirectResponse
    {
        $compliance_check->update($this->validated($request));

        return redirect()->route('admin.compliance-checks.show', $compliance_check)
            ->with('success', $this->message($compliance_check, 'Vérification de conformité mise à jour.'));
    }

    public function destroy(ComplianceCheck $compliance_check): RedirectResponse
    {
        $compliance_check->delete();

        return redirect()->route('admin.compliance-checks.index')
            ->with('success', 'Vérification de conformité supprimée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'environmental_claim_id' => ['required', 'exists:environmental_claims,id'],
            'check_type' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(ComplianceCheck::STATUSES)],
            'result_summary' => [Rule::requiredIf(fn () => in_array($request->status, ['passed', 'failed'], true)), 'nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'checked_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        // The product always follows the audited claim, so the two can never disagree
        $data['product_id'] = EnvironmentalClaim::whereKey($data['environmental_claim_id'])->value('product_id');

        if (in_array($data['status'], ['passed', 'failed'], true) && empty($data['checked_at'])) {
            $data['checked_at'] = now()->toDateString();
        }

        return $data;
    }

    private function message(ComplianceCheck $check, string $base): string
    {
        return $check->status === 'failed'
            ? $base.' Une alerte a été créée automatiquement et la déclaration a été rejetée.'
            : $base;
    }
}

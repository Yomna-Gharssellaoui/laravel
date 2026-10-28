@php($item = $item ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="environmental_claim_id" value="Déclaration environnementale" />
        <select name="environmental_claim_id" id="environmental_claim_id" class="tv-input mt-1" required>
            <option value="">—</option>
            @foreach($environmentalClaims as $claim)
                <option value="{{ $claim->id }}" @selected(old('environmental_claim_id', $item?->environmental_claim_id ?? $selectedClaim) == $claim->id)>{{ $claim->title }} — {{ $claim->product?->name ?? 'sans produit' }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Le produit est repris automatiquement depuis la déclaration.</p>
        <x-input-error :messages="$errors->get('environmental_claim_id')" class="mt-1" />
    </div>
    <div>
        <x-form.field label="Type de vérification" name="check_type" :value="old('check_type', $item?->check_type ?? 'document_review')" list="check-types" required />
        <datalist id="check-types">
            @foreach(\App\Models\ComplianceCheck::TYPES as $type)
                <option value="{{ $type }}">
            @endforeach
        </datalist>
    </div>
    <div>
        <x-input-label for="status" value="Statut" />
        <select name="status" id="status" class="tv-input mt-1" required>
            @foreach(\App\Models\ComplianceCheck::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $item?->status ?? 'pending') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">« failed » crée une alerte et rejette la déclaration ; « passed » la valide.</p>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>
    <x-form.field label="Date de vérification" name="checked_at" type="date" :value="old('checked_at', $item?->checked_at?->format('Y-m-d'))" />
    <div class="md:col-span-2">
        <x-input-label for="result_summary" value="Résultat (obligatoire si passed / failed)" />
        <textarea name="result_summary" id="result_summary" rows="3" class="tv-input mt-1">{{ old('result_summary', $item?->result_summary) }}</textarea>
        <x-input-error :messages="$errors->get('result_summary')" class="mt-1" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notes" />
        <textarea name="notes" id="notes" rows="3" class="tv-input mt-1">{{ old('notes', $item?->notes) }}</textarea>
    </div>
</div>

@php($item = $item ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <div>
        <x-input-label for="product_id" value="Produit" />
        <select name="product_id" id="product_id" class="tv-input mt-1" required>
            <option value="">—</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(old('product_id', $item?->product_id ?? request('product_id')) == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('product_id')" class="mt-1" />
    </div>
    <x-form.field label="Type" name="certificate_type" :value="old('certificate_type', $item?->certificate_type ?? '')" required />
    <x-form.field label="Émetteur" name="issuer" :value="old('issuer', $item?->issuer ?? '')" />
    <div>
        <x-form.field label="Numéro" name="certificate_number" :value="old('certificate_number', $item?->certificate_number ?? '')" />
    </div>
    <x-form.field label="Date d'émission" name="issue_date" type="date" :value="old('issue_date', $item?->issue_date?->format('Y-m-d'))" />
    <x-form.field label="Date d'expiration" name="expiry_date" type="date" :value="old('expiry_date', $item?->expiry_date?->format('Y-m-d'))" />
    <div>
        <x-input-label for="status" value="Statut" />
        @if(auth()->user()->isAdmin())
            <select name="status" id="status" class="tv-input mt-1" required>
                @foreach(\App\Models\Certificate::STATUSES as $st)
                    <option value="{{ $st }}" @selected(old('status', $item?->status ?? 'pending') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="status" value="pending">
            <p class="tv-input mt-1 bg-slate-50">pending — validé par un administrateur</p>
        @endif
        <p class="mt-1 text-xs text-slate-500">Les certifications expirées passent automatiquement en « expired » (tâche quotidienne).</p>
    </div>
    <div>
        <x-input-label for="document" value="Certificat (PDF, JPG, PNG — 5 Mo max)" />
        @if($item?->document_path)
            <a href="{{ $item->documentUrl() }}" target="_blank" class="tv-link text-sm">Voir le document actuel</a>
        @endif
        <input type="file" name="document" id="document" accept=".pdf,image/*" class="tv-input mt-1">
        <x-input-error :messages="$errors->get('document')" class="mt-1" />
    </div>
</div>

@php($item = $item ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <div>
        <x-input-label for="product_id" value="Produit" />
        <select name="product_id" id="product_id" class="tv-input mt-1" required>
            <option value="">—</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id', $item?->product_id) == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('product_id')" class="mt-1" />
    </div>
    <div>
        <x-form.field label="Type de déclaration" name="claim_type" :value="old('claim_type', $item?->claim_type)" list="claim-types" required />
        <datalist id="claim-types">
            @foreach(\App\Models\EnvironmentalClaim::TYPES as $type)
                <option value="{{ $type }}">
            @endforeach
        </datalist>
    </div>
    <x-form.field label="Titre" name="title" :value="old('title', $item?->title)" required />
    <div>
        <x-input-label for="status" value="Statut" />
        <select name="status" id="status" class="tv-input mt-1" required>
            @foreach(\App\Models\EnvironmentalClaim::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $item?->status ?? 'pending') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>
    <x-form.field label="Référence du document (facultatif)" name="source_document" :value="old('source_document', $item?->source_document)" />
    <div>
        <x-input-label for="document" value="Justificatif (PDF, JPG, PNG — 5 Mo max)" />
        @if($item?->document_path)
            <a href="{{ $item->documentUrl() }}" target="_blank" class="tv-link text-sm">Voir le document actuel</a>
        @endif
        <input type="file" name="document" id="document" accept=".pdf,image/*" class="tv-input mt-1">
        <x-input-error :messages="$errors->get('document')" class="mt-1" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea name="description" id="description" rows="3" class="tv-input mt-1">{{ old('description', $item?->description) }}</textarea>
    </div>
    <p class="md:col-span-2 text-sm text-slate-600">Le score de confiance est calculé par l’analyse de greenwashing (100 − score de risque).</p>
</div>

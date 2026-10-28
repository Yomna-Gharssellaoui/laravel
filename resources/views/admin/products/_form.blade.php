@php($item = $item ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <x-form.field label="Nom" name="name" :value="old('name', $item?->name ?? '')" required />
    <x-form.field label="Code-barres" name="barcode" :value="old('barcode', $item?->barcode ?? '')" />
    <x-form.field label="SKU" name="sku" :value="old('sku', $item?->sku ?? '')" />
    <x-form.field label="Catégorie" name="category" :value="old('category', $item?->category ?? '')" />
    <x-form.field label="Origine" name="origin" :value="old('origin', $item?->origin ?? '')" />
    <div>
        <x-input-label for="status" value="Statut" />
        <select name="status" id="status" class="tv-input mt-1" required>
            @foreach(\App\Models\Product::STATUSES as $st)
                <option value="{{ $st }}" @selected(old('status', $item?->status ?? 'draft') === $st)>{{ $st }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">La publication exige une empreinte environnementale et au moins une certification.</p>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>
    @if($canChooseProducer)
        <div>
            <x-input-label for="producer_id" value="Producteur" />
            <select name="producer_id" id="producer_id" class="tv-input mt-1">
                <option value="">—</option>
                @foreach($producers as $p)
                    <option value="{{ $p->id }}" @selected(old('producer_id', $item?->producer_id ?? '') == $p->id)>{{ $p->company_name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('producer_id')" class="mt-1" />
        </div>
    @else
        <div>
            <x-input-label value="Producteur" />
            <p class="tv-input mt-1 bg-slate-50">{{ $producers->first()?->company_name ?? $item?->producer?->company_name ?? '—' }}</p>
        </div>
    @endif
    <div>
        <x-input-label for="transformer_id" value="Transformateur" />
        <select name="transformer_id" id="transformer_id" class="tv-input mt-1" @disabled(auth()->user()->hasRole('transformer'))>
            <option value="">—</option>
            @foreach($transformers as $t)
                <option value="{{ $t->id }}" @selected(old('transformer_id', $item?->transformer_id ?? '') == $t->id)>{{ $t->company_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('transformer_id')" class="mt-1" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="ingredients" value="Ingrédients (séparés par des virgules)" />
        <textarea name="ingredients" id="ingredients" rows="3" class="tv-input mt-1">{{ old('ingredients', $item?->ingredients ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('ingredients')" class="mt-1" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="image" value="Image du produit (JPG/PNG, 2 Mo max)" />
        @if($item?->image_path)
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="h-20 w-20 rounded-lg object-cover border border-slate-200">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="remove_image" value="0">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300"> Supprimer l’image
                </label>
            </div>
        @endif
        <input type="file" name="image" id="image" accept="image/*" class="tv-input mt-2">
        <x-input-error :messages="$errors->get('image')" class="mt-1" />
    </div>
</div>

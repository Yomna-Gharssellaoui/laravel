@php
    $item = $item ?? null;
    $steps = old('steps', collect($item?->path_history ?? [])->map(fn ($s) => [
        'stage' => $s['stage'] ?? '',
        'location' => $s['location'] ?? '',
        'at' => ! empty($s['at']) ? \Illuminate\Support\Carbon::parse($s['at'])->format('Y-m-d\TH:i') : '',
        'lat' => $s['lat'] ?? '',
        'lon' => $s['lon'] ?? '',
    ])->all());
@endphp
<div class="grid gap-4 md:grid-cols-2">
    <div>
        <x-input-label for="product_id" value="Produit" />
        <select name="product_id" id="product_id" class="tv-input mt-1" required>
            <option value="">—</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(old('product_id', $item?->product_id ?? '') == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('product_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="distributor_id" value="Distributeur" />
        <select name="distributor_id" id="distributor_id" class="tv-input mt-1">
            @unless(auth()->user()->hasRole('distributor'))<option value="">—</option>@endunless
            @foreach($distributors as $d)
                <option value="{{ $d->id }}" @selected(old('distributor_id', $item?->distributor_id ?? '') == $d->id)>{{ $d->company_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('distributor_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="status" value="Statut" />
        <select name="status" id="status" class="tv-input mt-1" required>
            @foreach(\App\Models\SupplyChainTrace::STATUSES as $st)
                <option value="{{ $st }}" @selected(old('status', $item?->status ?? 'in_transit') === $st)>{{ $st }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-form.field label="Distance totale (km)" name="total_distance_km" type="number" step="0.01" min="0" :value="old('total_distance_km', $item?->total_distance_km ?? '')" />
        <p class="mt-1 text-xs text-slate-500">Laissez vide pour la calculer à partir des coordonnées GPS des étapes.</p>
    </div>
</div>

<div class="mt-6">
    <div class="flex items-center justify-between">
        <h3 class="tv-section-title mb-0">Étapes du parcours</h3>
        <button type="button" class="tv-btn-secondary" data-add-step>+ Ajouter une étape</button>
    </div>
    <p class="mt-1 text-xs text-slate-500">La dernière étape (par date) devient l’étape et la position actuelles.</p>
    <x-input-error :messages="collect($errors->get('steps.*'))->flatten()->unique()->all()" class="mt-1" />

    <datalist id="trace-stages">
        @foreach(\App\Models\SupplyChainTrace::STAGES as $stage)
            <option value="{{ $stage }}">
        @endforeach
    </datalist>

    <div class="mt-3 space-y-3" data-steps>
        @foreach($steps as $i => $step)
            @include('admin.supply-chain-traces._step', ['i' => $i, 'step' => $step])
        @endforeach
    </div>

    <template data-step-template>
        @include('admin.supply-chain-traces._step', ['i' => '__INDEX__', 'step' => []])
    </template>
</div>

<script>
    (() => {
        const list = document.querySelector('[data-steps]');
        const template = document.querySelector('[data-step-template]');
        let next = {{ count($steps) }};

        document.querySelector('[data-add-step]').addEventListener('click', () => {
            list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', next++));
        });

        list.addEventListener('click', (event) => {
            if (event.target.matches('[data-remove-step]')) {
                event.target.closest('[data-step]').remove();
            }
        });
    })();
</script>

@extends('layouts.backend')

@section('title', 'Modifier empreinte')
@section('page-title', 'Modifier empreinte')

@section('content')
    <div class="tv-card max-w-4xl">
        <form method="POST" action="{{ route('admin.environmental-footprints.update', $item) }}" class="space-y-6">
            @csrf
            @method('PUT')
                        <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="product_id" value="Produit" />
                    <select name="product_id" id="product_id" class="tv-input mt-1" required>
                        <option value="">Sélectionner…</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id', $item->product_id ?? '') == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('product_id')" class="mt-1" />
                </div>
                <x-form.field label="Émissions CO₂ (kg CO₂e / kg)" name="co2_emissions" type="number" step="0.01" :value="old('co2_emissions', $item->co2_emissions ?? '')" />
                <x-form.field label="Consommation d’eau (L / kg)" name="water_usage" type="number" step="0.01" :value="old('water_usage', $item->water_usage ?? '')" />
                <x-form.field label="Occupation des sols (m² / kg)" name="land_usage" type="number" step="0.01" :value="old('land_usage', $item->land_usage ?? '')" />
                <p class="md:col-span-2 text-sm text-slate-500">Le score environnemental (0-100) est calculé automatiquement : CO₂ 50 % (0 au-delà de 20 kg), eau 30 % (0 au-delà de 500 L), sols 20 % (0 au-delà de 10 m²).</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="tv-btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.environmental-footprints.show', $item) }}" class="tv-btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
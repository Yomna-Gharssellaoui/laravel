@extends('layouts.frontend')

@section('title', $product->name)

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap gap-3">
            <a href="{{ route('products.index') }}" class="tv-btn-secondary">Retour au catalogue</a>
            <a href="{{ route('products.trace', $product) }}" class="tv-btn-primary">Voir la traçabilité</a>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-r from-brand-800 via-brand-700 to-brand-500 p-8 text-white">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-brand-100">Produit</p>
                        <h1 class="mt-2 font-display text-4xl font-semibold">{{ $product->name }}</h1>
                    </div>
                    @if($product->environmentalFootprint)
                        <x-footprint-score :score="(int) $product->environmentalFootprint->ai_score" />
                    @endif
                </div>
            </div>
            <div class="grid gap-6 p-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-6">
                    <div class="tv-card">
                        <h2 class="tv-section-title">Informations principales</h2>
                        <dl class="tv-dl">
                            <div><dt>Catégorie</dt><dd>{{ $product->category ?? '—' }}</dd></div>
                            <div><dt>Origine</dt><dd>{{ $product->origin ?? '—' }}</dd></div>
                            <div><dt>Code-barres</dt><dd>{{ $product->barcode ?? '—' }}</dd></div>
                            <div><dt>SKU</dt><dd>{{ $product->sku ?? '—' }}</dd></div>
                            <div><dt>Producteur</dt><dd>{{ $product->producer?->company_name ?? '—' }}</dd></div>
                            <div><dt>Transformateur</dt><dd>{{ $product->transformer?->company_name ?? '—' }}</dd></div>
                            <div class="sm:col-span-2"><dt>Ingrédients</dt><dd>{{ $product->ingredients ?? '—' }}</dd></div>
                        </dl>
                    </div>

                    @if($product->environmentalFootprint)
                        <div class="tv-card">
                            <h2 class="tv-section-title">Empreinte environnementale</h2>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-xl bg-brand-50 p-4">
                                    <p class="text-xs uppercase tracking-wide text-brand-700">CO₂</p>
                                    <p class="mt-2 text-2xl font-semibold text-brand-800">{{ $product->environmentalFootprint->co2_emissions ?? '—' }} <span class="text-sm font-normal">kg CO₂e/kg</span></p>
                                </div>
                                <div class="rounded-xl bg-brand-50 p-4">
                                    <p class="text-xs uppercase tracking-wide text-brand-700">Eau</p>
                                    <p class="mt-2 text-2xl font-semibold text-brand-800">{{ $product->environmentalFootprint->water_usage ?? '—' }} <span class="text-sm font-normal">L/kg</span></p>
                                </div>
                                <div class="rounded-xl bg-brand-50 p-4">
                                    <p class="text-xs uppercase tracking-wide text-brand-700">Sols</p>
                                    <p class="mt-2 text-2xl font-semibold text-brand-800">{{ $product->environmentalFootprint->land_usage ?? '—' }} <span class="text-sm font-normal">m²/kg</span></p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="tv-card">
                        <h2 class="tv-section-title">Certifications</h2>
                        @if($product->certificates->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($product->certificates as $certificate)
                                    <x-certificate-badge :status="$certificate->status" :label="$certificate->certificate_type" />
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-600">Aucune certification enregistrée pour ce produit.</p>
                        @endif
                    </div>

                    <div class="tv-card">
                        <h2 class="tv-section-title">Avis consommateurs</h2>
                        @if($product->approvedReviews->isNotEmpty())
                            <div class="space-y-4">
                                @foreach($product->approvedReviews->take(3) as $review)
                                    <x-review-card :review="$review" />
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-600">Aucun avis vérifié n’a encore été publié pour ce produit.</p>
                        @endif

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            @auth
                                @php($mine = $product->reviews()->where('user_id', auth()->id())->first())
                                @if($product->status !== 'published')
                                    <p class="text-sm text-slate-600">Les avis sont ouverts une fois le produit publié.</p>
                                @elseif(! auth()->user()->hasVerifiedEmail())
                                    <p class="text-sm text-slate-600">Vérifiez votre adresse email pour donner votre avis.</p>
                                @else
                                    <h3 class="text-base font-semibold text-slate-800">{{ $mine ? 'Modifier mon avis' : 'Donner mon avis' }}</h3>
                                    @if($mine)
                                        <p class="mt-1 text-xs text-slate-500">Statut : {{ $mine->status === 'approved' ? 'publié' : ($mine->status === 'rejected' ? 'refusé par la modération' : 'en attente de modération') }}</p>
                                    @endif
                                    <form method="POST" action="{{ $mine ? route('reviews.update', $mine) : route('reviews.store', $product) }}" class="mt-3 space-y-3">
                                        @csrf
                                        @if($mine) @method('PUT') @endif
                                        <div class="flex flex-wrap gap-3">
                                            <select name="rating" class="tv-input w-32" required aria-label="Note">
                                                @foreach([5, 4, 3, 2, 1] as $n)
                                                    <option value="{{ $n }}" @selected((int) old('rating', $mine?->rating ?? 5) === $n)>{{ $n }} / 5</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="title" value="{{ old('title', $mine?->title) }}" maxlength="255" placeholder="Titre" class="tv-input flex-1">
                                        </div>
                                        <textarea name="comment" rows="3" maxlength="2000" placeholder="Votre avis" class="tv-input w-full">{{ old('comment', $mine?->comment) }}</textarea>
                                        <div class="flex gap-3">
                                            <button type="submit" class="tv-btn-primary">{{ $mine ? 'Mettre à jour' : 'Envoyer' }}</button>
                                        </div>
                                    </form>
                                    @if($mine)
                                        <form method="POST" action="{{ route('reviews.destroy', $mine) }}" class="mt-2" onsubmit="return confirm('Supprimer votre avis ?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:underline">Supprimer mon avis</button>
                                        </form>
                                    @endif
                                @endif
                            @else
                                <p class="text-sm text-slate-600"><a href="{{ route('login') }}" class="tv-link">Connectez-vous</a> pour donner votre avis.</p>
                            @endauth
                        </div>
                    </div>

                    <div class="tv-card">
                        <h2 class="tv-section-title">Déclarations environnementales M5</h2>
                        @if($product->environmentalClaims->isNotEmpty())
                            <div class="space-y-4">
                                @foreach($product->environmentalClaims as $claim)
                                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-xs uppercase tracking-[0.18em] text-emerald-700">{{ $claim->claim_type ?? 'Déclaration' }}</p>
                                                <h3 class="mt-1 text-lg font-semibold text-slate-800">{{ $claim->title }}</h3>
                                            </div>
                                            <span class="inline-flex rounded-full border border-emerald-300 bg-white px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                {{ ucfirst($claim->status ?? 'pending') }}
                                            </span>
                                        </div>

                                        <p class="mt-3 text-sm text-slate-700">{{ $claim->description }}</p>

                                        <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
                                            <span>Confiance: {{ $claim->confidence_score ?? 0 }}%</span>
                                            <span>Document: {{ $claim->source_document ?? 'Non référencé' }}</span>
                                        </div>

                                        @if($claim->complianceChecks->isNotEmpty())
                                            <div class="mt-4 space-y-2 border-t border-emerald-200 pt-3">
                                                @foreach($claim->complianceChecks as $check)
                                                    <div class="rounded-xl bg-white p-3">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-sm font-medium text-slate-700">{{ ucfirst(str_replace('_', ' ', $check->check_type ?? 'vérification')) }}</span>
                                                            <span class="text-xs font-medium {{ $check->status === 'passed' ? 'text-emerald-700' : ($check->status === 'failed' ? 'text-red-700' : 'text-amber-700') }}">
                                                                {{ str_replace('_', ' ', $check->status) }}
                                                            </span>
                                                        </div>
                                                        <p class="mt-1 text-xs text-slate-600">{{ $check->result_summary }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-600">Aucune déclaration environnementale n’a été publiée pour ce produit.</p>
                        @endif
                    </div>

                    <div class="tv-card">
                        <h2 class="tv-section-title">Vérifications de conformité</h2>
                        @if($product->complianceChecks->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($product->complianceChecks as $check)
                                    <div class="rounded-xl border border-slate-200 p-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-medium text-slate-700">{{ ucfirst(str_replace('_', ' ', $check->check_type ?? 'vérification')) }}</span>
                                            <span class="text-xs font-medium {{ $check->status === 'passed' ? 'text-emerald-700' : ($check->status === 'failed' ? 'text-red-700' : 'text-amber-700') }}">
                                                {{ str_replace('_', ' ', $check->status) }}
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-600">{{ $check->result_summary }}</p>
                                        @if($check->environmentalClaim)
                                            <p class="mt-2 text-xs text-slate-500">Déclaration: {{ $check->environmentalClaim->title }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-600">Aucune vérification de conformité enregistrée pour ce produit.</p>
                        @endif
                    </div>
                </div>

                <aside class="space-y-6">
                    @if($product->status === 'published')
                        <div class="tv-card">
                            <h2 class="tv-section-title">Une question sur ce produit ?</h2>
                            @auth
                                <p class="text-sm text-slate-600">L’assistant IA répond à partir des données de traçabilité du produit.</p>
                                <div class="mt-3 max-h-72 space-y-3 overflow-y-auto text-sm" data-chat-log aria-live="polite"></div>
                                <form class="mt-3 flex gap-2" data-chat-form action="{{ route('products.assistant', $product) }}">
                                    <input type="text" name="question" maxlength="500" required placeholder="Ex. : ce produit est-il vraiment bio ?" class="tv-input flex-1" aria-label="Votre question">
                                    <button type="submit" class="tv-btn-primary">Envoyer</button>
                                </form>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach(['D’où vient ce produit ?', 'Ses déclarations écologiques sont-elles vérifiées ?', 'Contient-il du gluten ?'] as $suggestion)
                                        <button type="button" class="rounded-full border border-brand-200 px-3 py-1 text-xs text-brand-700 hover:bg-brand-50" data-chat-suggestion>{{ $suggestion }}</button>
                                    @endforeach
                                </div>
                                <script>
                                    (() => {
                                        const form = document.querySelector('[data-chat-form]');
                                        const log = document.querySelector('[data-chat-log]');
                                        const input = form.querySelector('input');
                                        const bubble = (text, mine) => {
                                            const el = document.createElement('p');
                                            el.className = mine ? 'ml-8 rounded-xl bg-brand-600 px-3 py-2 text-white' : 'mr-8 rounded-xl bg-brand-50 px-3 py-2 text-slate-800';
                                            el.textContent = text;
                                            log.appendChild(el);
                                            log.scrollTop = log.scrollHeight;
                                            return el;
                                        };
                                        form.addEventListener('submit', async (event) => {
                                            event.preventDefault();
                                            const question = input.value.trim();
                                            if (! question) return;
                                            bubble(question, true);
                                            input.value = '';
                                            const pending = bubble('…', false);
                                            const button = form.querySelector('button');
                                            button.disabled = true;
                                            try {
                                                const response = await fetch(form.action, {
                                                    method: 'POST',
                                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                                    body: JSON.stringify({ question }),
                                                });
                                                const data = await response.json();
                                                pending.textContent = response.ok ? data.answer : (response.status === 429 ? 'Trop de questions, réessayez dans une minute.' : (data.message || 'Erreur, réessayez.'));
                                            } catch {
                                                pending.textContent = 'Erreur réseau, réessayez.';
                                            } finally {
                                                button.disabled = false;
                                            }
                                        });
                                        document.querySelectorAll('[data-chat-suggestion]').forEach((b) => b.addEventListener('click', () => {
                                            input.value = b.textContent.trim();
                                            form.requestSubmit();
                                        }));
                                    })();
                                </script>
                            @else
                                <p class="text-sm text-slate-600"><a href="{{ route('login') }}" class="tv-link">Connectez-vous</a> pour poser vos questions à l’assistant IA.</p>
                            @endauth
                        </div>

                        <div class="tv-card flex items-center gap-4">
                            <div class="shrink-0 rounded-lg border border-slate-200 bg-white p-1">{!! $product->traceQrCodeSvg(96) !!}</div>
                            <div class="text-sm">
                                <p class="font-semibold text-slate-800">QR code de traçabilité</p>
                                <p class="text-slate-600">Scannez-le pour ouvrir le parcours du produit.</p>
                                <a href="{{ route('products.qr', $product) }}" class="tv-link">Télécharger</a>
                            </div>
                        </div>
                    @endif

                    <div class="tv-card">
                        <h2 class="tv-section-title">Alertes publiques</h2>
                        @if($product->openAlerts->isNotEmpty())
                            @foreach($product->openAlerts as $alert)
                                <div class="mb-3 rounded-xl border border-red-100 bg-red-50 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <x-alert-badge :alert="$alert" />
                                        <span class="text-xs text-red-700">{{ $alert->severity }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-slate-800">{{ $alert->title }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-slate-600">Aucune alerte publique active pour ce produit.</p>
                        @endif
                    </div>

                    <div class="tv-card">
                        <h2 class="tv-section-title">Analyses IA</h2>
                        @if($product->aiAnalyses->isNotEmpty())
                            @foreach($product->aiAnalyses->take(2) as $analysis)
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-700">{{ $analysis->analysis_type ?? 'Analyse' }}</span>
                                        <x-footprint-score :score="(int) ($analysis->greenwashing_score ?? 0)" />
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Modèle : {{ $analysis->model_used ?? 'Démonstration' }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-slate-600">Aucune analyse de démonstration enregistrée.</p>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection

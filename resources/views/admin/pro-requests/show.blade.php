@extends('layouts.backend')

@section('title', 'Demande de compte pro')
@section('page-title', 'Demande de compte '.mb_strtolower($item->roleLabel()))

@section('content')
    <div class="mb-6"><a href="{{ route('admin.pro-requests.index') }}" class="tv-btn-secondary">Retour aux demandes</a></div>

    <div class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
        <div class="space-y-6">
            <div class="tv-card">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="tv-section-title mb-0">{{ $item->company_name }}</h2>
                    <x-status-badge :status="$item->status" />
                </div>
                <dl class="tv-dl">
                    <div><dt>Demandeur</dt><dd>{{ $item->user->name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $item->user->email }} @if($item->user->hasVerifiedEmail())<span class="text-emerald-700">(vérifié)</span>@endif</dd></div>
                    <div><dt>Rôle actuel</dt><dd><x-status-badge :status="$item->user->role" /></dd></div>
                    <div><dt>Compte demandé</dt><dd>{{ $item->roleLabel() }}</dd></div>
                    <div><dt>Localisation</dt><dd>{{ $item->location }}</dd></div>
                    <div><dt>Activité</dt><dd>{{ $item->activity_type ?? '—' }}</dd></div>
                    <div><dt>Téléphone</dt><dd>{{ $item->phone ?? '—' }}</dd></div>
                    <div><dt>Matricule / registre</dt><dd>{{ $item->registration_number ?? '—' }}</dd></div>
                    <div><dt>Envoyée le</dt><dd>{{ $item->created_at->format('d/m/Y H:i') }}</dd></div>
                    <div><dt>Inscrit depuis</dt><dd>{{ $item->user->created_at->format('d/m/Y') }}</dd></div>
                    <div class="sm:col-span-2"><dt>Présentation de l’activité</dt><dd class="whitespace-pre-line font-normal">{{ $item->motivation }}</dd></div>
                    <div class="sm:col-span-2"><dt>Justificatif</dt><dd>
                        @if($item->document_path)
                            <a href="{{ route('admin.pro-requests.document', $item) }}" class="tv-link">Télécharger le justificatif</a>
                        @else
                            Aucun justificatif joint
                        @endif
                    </dd></div>
                </dl>
            </div>

            @if($previous->isNotEmpty())
                <div class="tv-card">
                    <h2 class="tv-section-title">Demandes précédentes de ce compte</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach($previous as $old)
                            <li class="flex justify-between gap-3"><a href="{{ route('admin.pro-requests.show', $old) }}" class="tv-link">{{ $old->roleLabel() }} · {{ $old->company_name }} · {{ $old->created_at->format('d/m/Y') }}</a><x-status-badge :status="$old->status" /></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($item->isPending())
                <div class="tv-card">
                    <h2 class="tv-section-title">Accepter</h2>
                    <p class="text-sm text-slate-700">Le compte devient <strong>{{ mb_strtolower($item->roleLabel()) }}</strong> et sa fiche « {{ $item->company_name }} » est créée. Le demandeur reçoit un email.</p>
                    <form method="POST" action="{{ route('admin.pro-requests.approve', $item) }}" class="mt-4 space-y-3">
                        @csrf @method('PATCH')
                        <textarea name="admin_note" rows="2" maxlength="1000" class="tv-input" placeholder="Message au demandeur (facultatif)"></textarea>
                        <button type="submit" class="tv-btn-primary">Accepter la demande</button>
                    </form>
                </div>
                <div class="tv-card">
                    <h2 class="tv-section-title">Refuser</h2>
                    <form method="POST" action="{{ route('admin.pro-requests.reject', $item) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <textarea name="admin_note" rows="3" maxlength="1000" class="tv-input" placeholder="Motif du refus (obligatoire, envoyé au demandeur)">{{ old('admin_note') }}</textarea>
                        <x-input-error :messages="$errors->get('admin_note')" />
                        <button type="submit" class="tv-btn-danger">Refuser la demande</button>
                    </form>
                </div>
            @else
                <div class="tv-card">
                    <h2 class="tv-section-title">Décision</h2>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-slate-600">Statut</dt><dd class="font-medium text-slate-900">{{ $item->statusLabel() }}</dd></div>
                        <div><dt class="text-slate-600">Par</dt><dd class="font-medium text-slate-900">{{ $item->reviewer?->name ?? '—' }}, le {{ $item->reviewed_at?->format('d/m/Y H:i') }}</dd></div>
                        <div><dt class="text-slate-600">Message</dt><dd class="text-slate-800">{{ $item->admin_note ?? '—' }}</dd></div>
                    </dl>
                    @if($item->status === 'approved')
                        @php $profile = $item->user->{$item->requested_role}; @endphp
                        @if($profile)
                            <a href="{{ route('admin.'.$item->requested_role.'s.show', $profile) }}" class="tv-btn-secondary mt-4">Voir la fiche {{ mb_strtolower($item->roleLabel()) }}</a>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

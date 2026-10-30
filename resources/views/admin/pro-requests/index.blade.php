@extends('layouts.backend')

@section('title', 'Demandes de compte pro')
@section('page-title', 'Demandes de compte professionnel')

@section('content')
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach(\App\Models\ProAccountRequest::STATUSES + ['all' => 'Toutes'] as $key => $label)
            <a href="{{ route('admin.pro-requests.index', ['status' => $key]) }}"
               class="rounded-full border px-4 py-1.5 text-sm font-medium {{ $status === $key ? 'border-brand-700 bg-brand-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                {{ $label }}
                @if($key !== 'all')<span class="ml-1 {{ $status === $key ? 'text-white' : 'text-slate-600' }}">{{ $counts[$key] ?? 0 }}</span>@endif
            </a>
        @endforeach
        <form method="GET" class="ml-auto flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Entreprise, nom ou email…" class="tv-input w-64">
            <button type="submit" class="tv-btn-secondary">Rechercher</button>
        </form>
    </div>

    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr><th>Demandeur</th><th>Compte demandé</th><th>Entreprise</th><th>Activité</th><th>Envoyée le</th><th>Statut</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><span class="font-medium text-slate-900">{{ $item->user->name }}</span><br><span class="text-xs text-slate-600">{{ $item->user->email }}</span></td>
                            <td>{{ $item->roleLabel() }}</td>
                            <td>{{ $item->company_name }}<br><span class="text-xs text-slate-600">{{ $item->location }}</span></td>
                            <td>{{ $item->activity_type ?? '—' }}</td>
                            <td>{{ $item->created_at->format('d/m/Y') }}</td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td class="text-right"><a href="{{ route('admin.pro-requests.show', $item) }}" class="tv-link">{{ $item->isPending() ? 'Examiner' : 'Voir' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="Aucune demande" message="Aucune demande ne correspond à ce filtre." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection

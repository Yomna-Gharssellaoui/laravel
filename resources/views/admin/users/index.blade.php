@extends('layouts.backend')

@section('title', 'Utilisateurs')
@section('page-title', 'Utilisateurs')

@section('content')
    <div class="mb-6 grid gap-3 sm:grid-cols-5">
        @foreach(\App\Models\User::ROLES as $role)
            <a href="{{ route('admin.users.index', ['role' => $role]) }}" class="tv-card py-3 text-center hover:ring-2 hover:ring-brand-200">
                <p class="text-2xl font-semibold text-brand-800">{{ $counts[$role] ?? 0 }}</p>
                <p class="text-xs uppercase tracking-wide text-slate-600">{{ $role }}</p>
            </a>
        @endforeach
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou email…" class="tv-input w-56">
            <select name="role" class="tv-input">
                <option value="">Tous les rôles</option>
                @foreach(\App\Models\User::ROLES as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <select name="state" class="tv-input">
                <option value="">Tous les états</option>
                <option value="blocked" @selected(request('state') === 'blocked')>Suspendus</option>
                <option value="unverified" @selected(request('state') === 'unverified')>Email non vérifié</option>
            </select>
            <button class="tv-btn-secondary" type="submit">Filtrer</button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="tv-btn-primary">Ajouter</a>
    </div>

    <div class="tv-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Nom</th><th>Email</th><th>Rôle</th><th>État</th><th>Inscrit le</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->email }}</td>
                            <td><x-status-badge :status="$item->role" /></td>
                            <td>
                                @if($item->isBlocked())
                                    <span class="text-red-700 text-sm">Suspendu</span>
                                @elseif(! $item->hasVerifiedEmail())
                                    <span class="text-amber-800 text-sm">Non vérifié</span>
                                @else
                                    <span class="text-emerald-700 text-sm">Actif</span>
                                @endif
                            </td>
                            <td>{{ $item->created_at?->format('d/m/Y') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.users.show', $item) }}" class="tv-link">Voir</a>
                                <a href="{{ route('admin.users.edit', $item) }}" class="tv-link ml-2">Modifier</a>
                                @unless($item->is(auth()->user()))
                                    <form action="{{ route('admin.users.block', $item) }}" method="POST" class="inline ml-2">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="tv-link text-sm">{{ $item->isBlocked() ? 'Réactiver' : 'Suspendre' }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100">
                                <x-empty-state title="Aucun utilisateur" message="Aucun compte ne correspond à ces filtres." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $items->links() }}</div>
        @endif
    </div>
@endsection

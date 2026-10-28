<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $items = User::query()
            ->when($request->q, fn ($q, $term) => $q->where(fn ($query) => $query
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%')))
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->state === 'blocked', fn ($q) => $q->whereNotNull('blocked_at'))
            ->when($request->state === 'unverified', fn ($q) => $q->whereNull('email_verified_at'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('admin.users.index', compact('items', 'counts'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        // role is not mass-assignable: it is only ever set here, by an admin
        $user->role = $data['role'];
        $user->save();

        // Sends the verification email
        event(new Registered($user));

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Utilisateur créé. Un email de vérification lui a été envoyé.');
    }

    public function show(User $user): View
    {
        $user->load(['producer', 'transformer', 'distributor', 'consumer'])->loadCount('reviews');

        return view('admin.users.show', ['item' => $user]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['item' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Vous ne pouvez pas retirer votre propre rôle administrateur.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte ici.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé.');
    }

    public function toggleBlock(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas suspendre votre propre compte.');
        }

        $user->forceFill(['blocked_at' => $user->isBlocked() ? null : now()])->save();

        return back()->with('success', $user->isBlocked() ? 'Compte suspendu.' : 'Compte réactivé.');
    }

    public function resendVerification(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('error', 'Cette adresse est déjà vérifiée.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Email de vérification renvoyé.');
    }
}

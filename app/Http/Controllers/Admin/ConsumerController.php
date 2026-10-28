<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumerPreferencesRequest;
use App\Models\Consumer;
use App\Models\User;
use App\Services\RecommendationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsumerController extends Controller
{
    public function index(Request $request): View
    {
        $items = Consumer::with('user')
            ->withCount('personalRatings')
            ->when($request->q, function ($q) use ($request) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%')
                    ->orWhere('email', 'like', '%'.$request->q.'%'));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.consumers.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.consumers.create', ['users' => $this->availableUsers()]);
    }

    public function store(ConsumerPreferencesRequest $request, RecommendationEngine $engine): RedirectResponse
    {
        $item = Consumer::create(['user_id' => $this->validatedUser($request)] + $request->preferences());

        $engine->recompute($item);

        return redirect()->route('admin.consumers.show', $item)
            ->with('success', 'Profil consommateur créé et recommandations calculées.');
    }

    public function show(Consumer $consumer): View
    {
        $consumer->load(['user', 'personalRatings' => fn ($q) => $q->orderByDesc('personalized_score'), 'personalRatings.product']);

        return view('admin.consumers.show', ['item' => $consumer]);
    }

    public function edit(Consumer $consumer): View
    {
        return view('admin.consumers.edit', [
            'item' => $consumer,
            'users' => $this->availableUsers($consumer),
        ]);
    }

    public function update(ConsumerPreferencesRequest $request, Consumer $consumer, RecommendationEngine $engine): RedirectResponse
    {
        $consumer->update(['user_id' => $this->validatedUser($request, $consumer)] + $request->preferences());

        $engine->recompute($consumer);

        return redirect()->route('admin.consumers.show', $consumer)
            ->with('success', 'Profil consommateur mis à jour et recommandations recalculées.');
    }

    public function destroy(Consumer $consumer): RedirectResponse
    {
        $consumer->delete();

        return redirect()->route('admin.consumers.index')
            ->with('success', 'Profil consommateur supprimé.');
    }

    private function availableUsers(?Consumer $current = null)
    {
        return User::where('role', 'consumer')
            ->where(fn ($q) => $q->whereDoesntHave('consumer')->orWhere('id', $current?->user_id ?? 0))
            ->orderBy('name')
            ->get();
    }

    private function validatedUser(Request $request, ?Consumer $consumer = null): int
    {
        return (int) $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'consumer'),
                Rule::unique('consumers', 'user_id')->ignore($consumer),
            ],
        ])['user_id'];
    }
}

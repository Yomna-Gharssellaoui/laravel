<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsumerPreferencesRequest;
use App\Models\Consumer;
use App\Models\PersonalRating;
use App\Services\RecommendationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsumerController extends Controller
{
    public function profile(Request $request): View
    {
        $consumer = $this->resolveConsumer($request, true);
        $consumer->load(['personalRatings.product.environmentalFootprint']);

        return view('frontend.consumer.profile', compact('consumer'));
    }

    public function edit(Request $request): View
    {
        $consumer = $this->resolveConsumer($request, true);

        return view('frontend.consumer.edit', compact('consumer'));
    }

    public function update(ConsumerPreferencesRequest $request, RecommendationEngine $engine): RedirectResponse
    {
        $consumer = $this->resolveConsumer($request, true);

        $consumer->update($request->preferences());

        // Preferences changed: refresh the personalised scores straight away
        $engine->recompute($consumer);

        return redirect()->route('consumer.profile')
            ->with('success', 'Préférences mises à jour, vos recommandations ont été recalculées.');
    }

    public function recommendations(Request $request, RecommendationEngine $engine): View
    {
        $consumer = $this->resolveConsumer($request, true);

        if (! $consumer->personalRatings()->exists()) {
            $engine->recompute($consumer);
        }

        $ratings = PersonalRating::with(['product.environmentalFootprint', 'product.producer'])
            ->where('consumer_id', $consumer->id)
            ->whereHas('product', fn ($q) => $q->where('status', 'published'))
            ->orderByDesc('personalized_score')
            ->get();

        return view('frontend.consumer.recommendations', [
            'consumer' => $consumer,
            'recommended' => $ratings->where('personalized_score', '>', 0)->take(12),
            'excluded' => $ratings->where('personalized_score', 0),
        ]);
    }

    private function resolveConsumer(Request $request, bool $create = false): Consumer
    {
        $user = $request->user();
        abort_unless($user, 403);

        $consumer = $user->consumer;

        if (! $consumer && $create) {
            $consumer = Consumer::create([
                'user_id' => $user->id,
                'preferences' => [],
                'sustainability_level' => 'débutant',
            ]);
        }

        abort_unless($consumer && $consumer->user_id === $user->id, 403);

        return $consumer;
    }
}

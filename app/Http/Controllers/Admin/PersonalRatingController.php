<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalRating;
use App\Services\RecommendationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Personal ratings are computed by the RecommendationEngine; the admin can
 * browse, delete and recompute them but not type scores in by hand.
 */
class PersonalRatingController extends Controller
{
    public function index(Request $request): View
    {
        $items = PersonalRating::with(['consumer.user', 'product'])
            ->when($request->q, function ($q) use ($request) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->q.'%'));
            })
            ->orderByDesc('personalized_score')
            ->paginate(12)
            ->withQueryString();

        return view('admin.personal-ratings.index', compact('items'));
    }

    public function show(PersonalRating $personal_rating): View
    {
        $personal_rating->load(['consumer.user', 'product']);

        return view('admin.personal-ratings.show', ['item' => $personal_rating]);
    }

    public function destroy(PersonalRating $personal_rating): RedirectResponse
    {
        $personal_rating->delete();

        return redirect()->route('admin.personal-ratings.index')
            ->with('success', 'Évaluation personnalisée supprimée.');
    }

    public function recompute(RecommendationEngine $engine): RedirectResponse
    {
        $count = $engine->recomputeAll();

        return redirect()->route('admin.personal-ratings.index')
            ->with('success', "Recommandations recalculées pour {$count} consommateur(s).");
    }
}

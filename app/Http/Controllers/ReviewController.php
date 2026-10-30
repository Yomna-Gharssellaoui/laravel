<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\Ml\MlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product, MlService $ml): RedirectResponse
    {
        abort_unless($product->status === 'published', 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // Module M4: a review the model is almost sure is spam is rejected straight away
        $analysis = $ml->reviewAnalysis(trim(($data['title'] ?? '').'. '.($data['comment'] ?? ''), '. '));
        $status = ($analysis['spam_probability'] ?? 0) >= 90 ? 'rejected' : 'pending';

        Review::updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
            ],
            $data + ['status' => $status, 'verified_purchase' => false]
        );

        return back()->with('success', 'Votre avis a été soumis et sera modéré avant publication.');
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        // Only the author edits a review; admins moderate it from the back-office
        abort_unless($request->user()->id === $review->user_id, 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // An edited review goes back to moderation
        $review->update($data + ['status' => 'pending']);

        return back()->with('success', 'Avis mis à jour (en attente de modération).');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        abort_unless($request->user()->id === $review->user_id || $request->user()->isAdmin(), 403);

        $review->delete();

        return back()->with('success', 'Avis supprimé.');
    }
}

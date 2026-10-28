<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reviews are written by consumers on the public site; the back-office moderates them.
 */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $items = Review::with(['product', 'user'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->rating, fn ($q) => $q->where('rating', $request->rating))
            ->when($request->q, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('title', 'like', '%'.$request->q.'%')
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$request->q.'%'));
                });
            })
            // Pending reviews first: they are the moderator's to-do list
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.reviews.index', compact('items'));
    }

    public function show(Review $review): View
    {
        $review->load(['product', 'user']);

        return view('admin.reviews.show', ['item' => $review]);
    }

    public function moderate(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Review::STATUSES)],
            'verified_purchase' => ['sometimes', 'boolean'],
        ]);

        $review->update($data);

        return back()->with('success', match ($data['status']) {
            'approved' => 'Avis approuvé et publié.',
            'rejected' => 'Avis refusé.',
            default => 'Avis remis en attente.',
        });
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Avis supprimé.');
    }
}

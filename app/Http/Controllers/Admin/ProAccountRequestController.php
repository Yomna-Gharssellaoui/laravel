<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProAccountRequest;
use App\Notifications\ProAccountRequestDecided;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProAccountRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $items = ProAccountRequest::with('user')
            ->when(array_key_exists($status, ProAccountRequest::STATUSES), fn ($q) => $q->where('status', $status))
            ->when($request->q, fn ($q) => $q->where(fn ($query) => $query
                ->where('company_name', 'like', '%'.$request->q.'%')
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%'))))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = ProAccountRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pro-requests.index', compact('items', 'counts', 'status'));
    }

    public function show(ProAccountRequest $proRequest): View
    {
        $proRequest->load(['user', 'reviewer']);

        return view('admin.pro-requests.show', [
            'item' => $proRequest,
            'previous' => ProAccountRequest::where('user_id', $proRequest->user_id)->whereKeyNot($proRequest->id)->latest()->get(),
        ]);
    }

    public function approve(Request $request, ProAccountRequest $proRequest): RedirectResponse
    {
        abort_unless($proRequest->isPending(), 409, 'Cette demande a déjà été traitée.');
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        if ($proRequest->user->role !== 'consumer') {
            return back()->withErrors(['admin_note' => 'Ce compte n’est plus un compte consommateur : refusez la demande.']);
        }

        $proRequest->approve($request->user(), $data['admin_note'] ?? null);
        $proRequest->user->notify(new ProAccountRequestDecided($proRequest));

        return redirect()->route('admin.pro-requests.show', $proRequest)
            ->with('success', 'Demande acceptée : '.$proRequest->user->name.' est maintenant '.mb_strtolower($proRequest->roleLabel()).'.');
    }

    public function reject(Request $request, ProAccountRequest $proRequest): RedirectResponse
    {
        abort_unless($proRequest->isPending(), 409, 'Cette demande a déjà été traitée.');
        $data = $request->validate(['admin_note' => ['required', 'string', 'min:5', 'max:1000']], [
            'admin_note.required' => 'Indiquez le motif du refus : il est envoyé au demandeur.',
        ]);

        $proRequest->reject($request->user(), $data['admin_note']);
        $proRequest->user->notify(new ProAccountRequestDecided($proRequest));

        return redirect()->route('admin.pro-requests.show', $proRequest)->with('success', 'Demande refusée, le demandeur a été prévenu.');
    }

    public function document(ProAccountRequest $proRequest): StreamedResponse
    {
        abort_unless($proRequest->document_path && Storage::disk('local')->exists($proRequest->document_path), 404);

        return Storage::disk('local')->download($proRequest->document_path, 'justificatif-'.$proRequest->id.'.'.pathinfo($proRequest->document_path, PATHINFO_EXTENSION));
    }
}

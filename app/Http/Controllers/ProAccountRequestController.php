<?php

namespace App\Http\Controllers;

use App\Models\ProAccountRequest;
use App\Models\User;
use App\Notifications\ProAccountRequestSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Demander un compte professionnel": a consumer applies to become a supply-chain actor.
 */
class ProAccountRequestController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();

        return view('frontend.pro-account.create', [
            'pending' => $user->pendingProAccountRequest(),
            'history' => $user->proAccountRequests()->get(),
            'canApply' => $user->role === 'consumer',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'consumer', 403, 'Votre compte est déjà un compte professionnel.');

        if ($user->pendingProAccountRequest()) {
            return back()->withErrors(['requested_role' => 'Vous avez déjà une demande en cours d’examen.']);
        }

        $data = $request->validate([
            'requested_role' => ['required', Rule::in(array_keys(ProAccountRequest::ROLES))],
            'company_name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', 'string', Rule::in(ProAccountRequest::ACTIVITIES[$request->input('requested_role')] ?? [])],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +().-]{6,30}$/'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'motivation' => ['required', 'string', 'min:20', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'terms' => ['accepted'],
        ], [
            'activity_type.in' => 'Choisissez une activité correspondant au type de compte demandé.',
            'terms.accepted' => 'Vous devez certifier l’exactitude des informations.',
        ]);

        $proRequest = new ProAccountRequest(collect($data)->except(['document', 'terms'])->all());
        if ($request->hasFile('document')) {
            // Private disk: the proof is only downloadable by admins
            $proRequest->document_path = $request->file('document')->store('pro-requests', 'local');
        }
        $user->proAccountRequests()->save($proRequest);

        Notification::send(User::where('role', 'admin')->whereNull('blocked_at')->get(), new ProAccountRequestSubmitted($proRequest));

        return redirect()->route('pro-account.create')
            ->with('success', 'Votre demande a été envoyée. Vous recevrez un email dès qu’elle aura été examinée.');
    }

    public function destroy(Request $request, ProAccountRequest $proAccountRequest): RedirectResponse
    {
        abort_unless($proAccountRequest->user_id === $request->user()->id && $proAccountRequest->isPending(), 403);

        $proAccountRequest->deleteDocument();
        $proAccountRequest->delete();

        return redirect()->route('pro-account.create')->with('success', 'Votre demande a été annulée.');
    }
}

@extends('layouts.frontend')

@section('title', 'Demander un compte professionnel')

@section('content')
    <x-page-header eyebrow="Compte professionnel" title="Rejoindre Trace Verte en tant que professionnel"
        subtitle="Producteurs, transformateurs et distributeurs : publiez vos produits, vos certifications et vos traces logistiques." />

    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        @if(! $canApply)
            <div class="tv-card">
                <h2 class="tv-section-title">Vous avez déjà un compte professionnel</h2>
                <p class="text-sm text-slate-700">Votre compte est un compte <strong>{{ auth()->user()->role }}</strong>. Gérez vos données depuis le back-office.</p>
                <a href="{{ route('admin.dashboard') }}" class="tv-btn-primary mt-4">Accéder au back-office</a>
            </div>
        @elseif($pending)
            <div class="tv-card border-l-4 border-amber-400">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="tv-section-title mb-1">Demande en cours d’examen</h2>
                        <p class="text-sm text-slate-700">Compte <strong>{{ mb_strtolower($pending->roleLabel()) }}</strong> pour « {{ $pending->company_name }} », envoyée le {{ $pending->created_at->format('d/m/Y à H:i') }}.</p>
                        <p class="mt-1 text-sm text-slate-600">Un administrateur vérifie vos informations. Vous recevrez un email avec la décision.</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">En attente</span>
                </div>
                <form method="POST" action="{{ route('pro-account.destroy', $pending) }}" class="mt-4" onsubmit="return confirm('Annuler votre demande ?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="tv-btn-secondary">Annuler ma demande</button>
                </form>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[1fr_0.45fr]">
                <form method="POST" action="{{ route('pro-account.store') }}" enctype="multipart/form-data" class="tv-card space-y-5" data-pro-form>
                    @csrf
                    <div>
                        <x-input-label value="Type de compte demandé" />
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            @foreach(\App\Models\ProAccountRequest::ROLES as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-300 p-3 text-sm font-medium text-slate-800 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                    <input type="radio" name="requested_role" value="{{ $value }}" @checked(old('requested_role', 'producer') === $value) class="text-brand-700 focus:ring-brand-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('requested_role')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="company_name" value="Nom de l’entreprise / exploitation" />
                            <input id="company_name" name="company_name" value="{{ old('company_name') }}" required maxlength="255" class="tv-input mt-1">
                            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="location" value="Ville / gouvernorat" />
                            <input id="location" name="location" value="{{ old('location') }}" required maxlength="255" placeholder="Ex. : Sfax, Tunisie" class="tv-input mt-1">
                            <x-input-error :messages="$errors->get('location')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="activity_type" value="Activité" />
                            <select id="activity_type" name="activity_type" required class="tv-input mt-1" data-activity>
                                @foreach(\App\Models\ProAccountRequest::ACTIVITIES as $role => $activities)
                                    @foreach($activities as $activity)
                                        <option value="{{ $activity }}" data-role="{{ $role }}" @selected(old('activity_type') === $activity)>{{ ucfirst($activity) }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('activity_type')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Téléphone (facultatif)" />
                            <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="+216 …" class="tv-input mt-1">
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="registration_number" value="Matricule fiscal / registre de commerce (facultatif)" />
                            <input id="registration_number" name="registration_number" value="{{ old('registration_number') }}" maxlength="50" class="tv-input mt-1">
                            <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="motivation" value="Présentez votre activité" />
                        <textarea id="motivation" name="motivation" rows="4" required minlength="20" maxlength="2000" class="tv-input mt-1" placeholder="Ce que vous produisez, transformez ou transportez, vos volumes, vos certifications…">{{ old('motivation') }}</textarea>
                        <x-input-error :messages="$errors->get('motivation')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="document" value="Justificatif (facultatif) : PDF, JPG ou PNG, 5 Mo maximum" />
                        <input id="document" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-800">
                        <x-input-error :messages="$errors->get('document')" class="mt-2" />
                    </div>

                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        Je certifie que ces informations sont exactes et j’accepte qu’elles soient vérifiées.
                    </label>
                    <x-input-error :messages="$errors->get('terms')" />

                    <button type="submit" class="tv-btn-primary">Envoyer ma demande</button>
                </form>

                <aside class="space-y-4">
                    <div class="tv-card">
                        <h2 class="tv-section-title">Comment ça marche ?</h2>
                        <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-700">
                            <li>Vous remplissez ce formulaire.</li>
                            <li>Un administrateur vérifie vos informations et votre justificatif.</li>
                            <li>Vous recevez un email avec la décision.</li>
                            <li>Si elle est acceptée, votre fiche professionnelle est créée et vous accédez au back-office.</li>
                        </ol>
                    </div>
                    <div class="tv-card text-sm text-slate-700">
                        <p class="font-semibold text-slate-800">Vos données consommateur sont conservées</p>
                        <p class="mt-1">Vos avis restent publiés. Une seule demande peut être en cours à la fois.</p>
                    </div>
                </aside>
            </div>

            <script>
                (() => {
                    const form = document.querySelector('[data-pro-form]');
                    const select = form.querySelector('[data-activity]');
                    const sync = () => {
                        const role = form.querySelector('input[name=requested_role]:checked')?.value;
                        let firstVisible = null;
                        [...select.options].forEach((option) => {
                            const visible = option.dataset.role === role;
                            option.hidden = ! visible;
                            option.disabled = ! visible;
                            if (visible && ! firstVisible) firstVisible = option;
                        });
                        if (select.selectedOptions[0]?.disabled) select.value = firstVisible?.value ?? '';
                    };
                    form.querySelectorAll('input[name=requested_role]').forEach((radio) => radio.addEventListener('change', sync));
                    sync();
                })();
            </script>
        @endif

        @if($history->where('status', '!=', 'pending')->isNotEmpty())
            <div class="tv-card mt-6">
                <h2 class="tv-section-title">Mes demandes précédentes</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($history->where('status', '!=', 'pending') as $old)
                        <li class="py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-slate-800">{{ $old->roleLabel() }} · {{ $old->company_name }} · {{ $old->created_at->format('d/m/Y') }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $old->status === 'approved' ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">{{ $old->statusLabel() }}</span>
                            </div>
                            @if($old->admin_note)
                                <p class="mt-1 text-slate-600">Message de l’équipe : {{ $old->admin_note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
@endsection

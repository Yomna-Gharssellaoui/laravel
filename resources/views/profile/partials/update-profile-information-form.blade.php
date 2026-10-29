<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ 'Informations du compte' }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ 'Modifiez votre nom et votre adresse email.' }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex items-center gap-5">
            <x-avatar :user="$user" class="h-20 w-20 text-2xl" />
            <div class="flex-1">
                <x-input-label for="avatar" :value="'Photo de profil (JPG, PNG, WebP — 2 Mo max)'" />
                <input id="avatar" name="avatar" type="file" accept="image/*" class="tv-input mt-1 w-full">
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
                @if($user->avatar_path)
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                        <input type="hidden" name="remove_avatar" value="0">
                        <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300"> Supprimer la photo
                    </label>
                @endif
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="'Nom'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ 'Votre adresse email n’est pas vérifiée.' }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ 'Cliquez ici pour renvoyer l’email de vérification.' }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ 'Un nouveau lien de vérification a été envoyé à votre adresse email.' }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ 'Enregistrer' }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ 'Enregistré.' }}</p>
            @endif
        </div>
    </form>
</section>

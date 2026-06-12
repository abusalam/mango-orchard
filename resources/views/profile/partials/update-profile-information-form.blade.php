<section>
    <header>
        <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="avatar" :value="__('Profile photo')" />
            <div class="mt-2 flex items-start gap-4">
                <x-user-avatar :user="$user" size="lg" />
                <div class="min-w-0 flex-1">
                    {{-- Form defaults to urlencoded; promote to multipart only
                         when a file is actually selected so the upload transmits. --}}
                    <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp"
                           onchange="this.files.length && (this.form.enctype = 'multipart/form-data')"
                           class="block w-full text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-medium file:bg-stone-900 file:text-amber-50 hover:file:bg-stone-800"
                           data-max-bytes="{{ \App\Support\UploadLimits::effectiveBytes(2048) }}"
                           data-testid="avatar-input">
                    @if ($user->avatar_path)
                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded text-rose-500 focus:ring-rose-400" data-testid="remove-avatar">
                            <span>Remove current photo</span>
                        </label>
                    @endif
                    <x-image-upload-guide
                        dimensions="400 × 400 px"
                        aspect="1:1 (square)"
                        :max-kb="2048"
                        note="Shown next to your name across the site. Square images crop best." />
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-stone-800 dark:text-stone-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-400">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-emerald-700">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-stone-600 dark:text-stone-300"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

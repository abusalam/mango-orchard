<x-guest-layout title="Site setup" :wide="true">
    <div class="p-2 sm:p-4">
        <header class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">Welcome — let's set up {{ config('app.name') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">
                Three quick steps: confirm the site identity, create the administrator account, and (optionally) upload a logo.
                You can change everything later from <span class="font-medium">Admin → Settings</span>.
            </p>
        </header>

        <form method="POST" action="{{ route('setup.store') }}" enctype="multipart/form-data" class="space-y-8" data-testid="setup-form">
            @csrf

            {{-- ── Step 1 · Site identity (read-only, from .env) ── --}}
            <fieldset>
                <legend class="flex items-center gap-2 text-base font-semibold text-stone-900 dark:text-stone-100">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-semibold">1</span>
                    Site identity
                </legend>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Read from your <code>.env</code> — edit <code>APP_NAME</code>, <code>APP_TAGLINE</code>, <code>APP_OWNER</code>, <code>APP_DISTRICT</code> there if these look wrong, then reload this page.</p>
                <dl class="mt-3 grid sm:grid-cols-2 gap-2 text-sm rounded-xl border border-stone-200 dark:border-stone-700 p-4 bg-stone-50 dark:bg-stone-900">
                    <div><dt class="text-stone-500 dark:text-stone-400 text-xs">Name</dt><dd class="font-medium text-stone-900 dark:text-stone-100">{{ config('app.name') }}</dd></div>
                    <div><dt class="text-stone-500 dark:text-stone-400 text-xs">Tagline</dt><dd class="font-medium text-stone-900 dark:text-stone-100">{{ config('app.tagline') }}</dd></div>
                    <div><dt class="text-stone-500 dark:text-stone-400 text-xs">Owner</dt><dd class="font-medium text-stone-900 dark:text-stone-100">{{ config('app.owner') }}</dd></div>
                    <div><dt class="text-stone-500 dark:text-stone-400 text-xs">District</dt><dd class="font-medium text-stone-900 dark:text-stone-100">{{ config('app.district') }}</dd></div>
                </dl>
            </fieldset>

            {{-- ── Step 2 · Administrator account ── --}}
            <fieldset class="pt-6 border-t border-stone-100 dark:border-stone-800">
                <legend class="flex items-center gap-2 text-base font-semibold text-stone-900 dark:text-stone-100">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-semibold">2</span>
                    Administrator account
                </legend>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">This account becomes the superuser — it holds every permission and can grant roles to everyone else.</p>

                <div class="mt-4 grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus data-testid="setup-name" />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required data-testid="setup-email" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" data-testid="setup-password" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Confirm password" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" data-testid="setup-password-confirmation" />
                    </div>
                </div>
            </fieldset>

            {{-- ── Step 3 · Logo (optional) ── --}}
            <fieldset class="pt-6 border-t border-stone-100 dark:border-stone-800">
                <legend class="flex items-center gap-2 text-base font-semibold text-stone-900 dark:text-stone-100">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-semibold">3</span>
                    Logo <span class="text-xs font-normal text-stone-500 dark:text-stone-400">(optional)</span>
                </legend>

                <div class="mt-4 flex items-start gap-4">
                    {{-- Live preview of what renders today: the generated monogram. --}}
                    <div class="shrink-0 text-center">
                        <x-site-logo size="auth" />
                        <p class="mt-1 text-[10px] text-stone-500 dark:text-stone-400">current</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-stone-600 dark:text-stone-300">Skip this and the site uses a generated monogram (your app name's initials over the brand gradient — shown left). Upload any time later under Admin → Settings.</p>
                        <input type="file" name="logo" id="logo" accept="image/jpeg,image/png,image/webp"
                               class="mt-2 block w-full text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-medium file:bg-stone-900 file:text-amber-50 hover:file:bg-stone-800"
                               data-max-bytes="{{ \App\Support\UploadLimits::effectiveBytes(2048) }}"
                               data-testid="setup-logo">
                        <x-image-upload-guide
                            dimensions="512 × 512 px"
                            aspect="1:1 (square)"
                            :max-kb="2048"
                            note="Re-encoded to a 512px WebP. Shown in the nav, footer, hero, and as the favicon." />
                        <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                    </div>
                </div>
            </fieldset>

            <div class="flex items-center gap-3 pt-4 border-t border-stone-100 dark:border-stone-800">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm" data-testid="setup-submit">
                    Finish setup
                </button>
                <p class="text-xs text-stone-500 dark:text-stone-400">You'll be signed in as the administrator.</p>
            </div>
        </form>
    </div>
</x-guest-layout>

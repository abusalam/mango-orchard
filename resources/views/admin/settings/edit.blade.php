<x-admin-layout title="Settings" active="settings">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Settings</h1>
        <p class="mt-1 text-stone-600">App-wide toggles. Changes take effect immediately.</p>
    </header>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white rounded-2xl border border-stone-200 p-6 sm:p-8 space-y-6"
          x-data="{ captchaOn: {{ $captchaEnabled ? 'true' : 'false' }} }">
        @csrf
        @method('PUT')

        <fieldset>
            <legend class="text-lg font-semibold text-stone-900">Captcha</legend>
            <p class="mt-1 text-sm text-stone-600">Show an image captcha on the login and registration forms to slow down bots.</p>

            <label class="mt-4 flex items-start gap-3 p-4 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                <input type="hidden" name="captcha_enabled" value="0">
                <input type="checkbox" name="captcha_enabled" id="captcha_enabled" value="1"
                       @checked($captchaEnabled)
                       x-model="captchaOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="captcha-toggle">
                <span>
                    <span class="block font-medium text-stone-900">Require captcha on login &amp; registration</span>
                    <span class="block text-xs text-stone-500 mt-0.5">
                        Currently
                        <strong x-text="captchaOn ? 'enabled' : 'disabled'">{{ $captchaEnabled ? 'enabled' : 'disabled' }}</strong>.
                    </span>
                </span>
            </label>
            @error('captcha_enabled') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

            <label class="mt-3 flex items-start gap-3 p-4 rounded-xl border border-stone-200 transition-colors cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50"
                   :class="{ 'opacity-50 cursor-not-allowed': !captchaOn }">
                <input type="hidden" name="captcha_autosolve" value="0">
                <input type="checkbox" name="captcha_autosolve" id="captcha_autosolve" value="1"
                       @checked($captchaAutosolve)
                       :disabled="!captchaOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="autosolve-toggle">
                <span>
                    <span class="block font-medium text-stone-900">Autosolve captcha (dev / test mode)</span>
                    <span class="block text-xs text-stone-500 mt-0.5">
                        Captcha image is still rendered, but the server accepts any answer.
                        Useful for development and end-to-end tests. <strong class="text-rose-700">Do not enable in production.</strong>
                    </span>
                </span>
            </label>
            @error('captcha_autosolve') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset class="pt-4 border-t border-stone-100">
            <legend class="text-lg font-semibold text-stone-900">Developer convenience</legend>
            <p class="mt-1 text-sm text-stone-600">Helpers that make manual testing faster. Should never be on in production.</p>

            <label class="mt-4 flex items-start gap-3 p-4 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                <input type="hidden" name="form_autofill" value="0">
                <input type="checkbox" name="form_autofill" id="form_autofill" value="1"
                       @checked($formAutofill)
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="autofill-toggle">
                <span>
                    <span class="block font-medium text-stone-900">Prefill empty form fields with faker data</span>
                    <span class="block text-xs text-stone-500 mt-0.5">
                        On page load, populates empty text/email/textarea/select inputs with realistic-looking sample data based on field names.
                        Passwords, hidden fields, checkboxes and captcha inputs are never touched.
                        <strong class="text-rose-700">Do not enable in production.</strong>
                    </span>
                </span>
            </label>
            @error('form_autofill') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </fieldset>

        <div class="flex items-center gap-3 pt-4 border-t border-stone-100">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                Save settings
            </button>
        </div>
    </form>
</x-admin-layout>

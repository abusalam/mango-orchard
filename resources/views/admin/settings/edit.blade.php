<x-admin-layout title="Settings" active="settings">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Settings</h1>
        <p class="mt-1 text-stone-600">App-wide toggles. Changes take effect immediately.</p>
    </header>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 sm:p-8 space-y-6"
          x-data="{ captchaOn: {{ $captchaEnabled ? 'true' : 'false' }} }">
        @csrf
        @method('PUT')

        <fieldset>
            <legend class="text-lg font-semibold text-stone-900 dark:text-stone-100">Captcha</legend>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">Show an image captcha on the login and registration forms to slow down bots.</p>

            <label class="mt-4 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-950 transition-colors">
                <input type="hidden" name="captcha_enabled" value="0">
                <input type="checkbox" name="captcha_enabled" id="captcha_enabled" value="1"
                       @checked($captchaEnabled)
                       x-model="captchaOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="captcha-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Require captcha on login &amp; registration</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Currently
                        <strong x-text="captchaOn ? 'enabled' : 'disabled'">{{ $captchaEnabled ? 'enabled' : 'disabled' }}</strong>.
                    </span>
                </span>
            </label>
            @error('captcha_enabled') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

            <label class="mt-3 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 transition-colors cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-950"
                   :class="{ 'opacity-50 cursor-not-allowed': !captchaOn }">
                <input type="hidden" name="captcha_autosolve" value="0">
                <input type="checkbox" name="captcha_autosolve" id="captcha_autosolve" value="1"
                       @checked($captchaAutosolve)
                       :disabled="!captchaOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="autosolve-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Autosolve captcha (dev / test mode)</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Captcha image is still rendered, but the server accepts any answer.
                        Useful for development and end-to-end tests. <strong class="text-rose-700 dark:text-rose-400">Do not enable in production.</strong>
                    </span>
                </span>
            </label>
            @error('captcha_autosolve') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset class="pt-4 border-t border-stone-100 dark:border-stone-800">
            <legend class="text-lg font-semibold text-stone-900 dark:text-stone-100">Developer convenience</legend>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">Helpers that make manual testing faster. Should never be on in production.</p>

            <label class="mt-4 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-950 transition-colors">
                <input type="hidden" name="form_autofill" value="0">
                <input type="checkbox" name="form_autofill" id="form_autofill" value="1"
                       @checked($formAutofill)
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="autofill-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Prefill empty form fields with faker data</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        On page load, populates empty text/email/textarea/select inputs with realistic-looking sample data based on field names.
                        Passwords, hidden fields, checkboxes and captcha inputs are never touched.
                        <strong class="text-rose-700 dark:text-rose-400">Do not enable in production.</strong>
                    </span>
                </span>
            </label>
            @error('form_autofill') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset class="pt-4 border-t border-stone-100 dark:border-stone-800">
            <legend class="text-lg font-semibold text-stone-900 dark:text-stone-100">Site mode</legend>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">Freeze writes site-wide &mdash; useful during migrations, incidents, or maintenance windows.</p>

            <label class="mt-4 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 cursor-pointer hover:border-rose-300 dark:hover:border-rose-700 has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50 dark:has-[:checked]:bg-rose-950 transition-colors">
                <input type="hidden" name="readonly_mode" value="0">
                <input type="checkbox" name="readonly_mode" id="readonly_mode" value="1"
                       @checked($readonlyMode)
                       class="mt-1 rounded text-rose-500 focus:ring-rose-400"
                       data-testid="readonly-mode-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Read-only mode</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Blocks all create / update / delete actions for non-superusers. Sign in, sign out, password reset, and impersonation-stop stay available. Superusers can still write &mdash; including toggling this setting back off.
                    </span>
                </span>
            </label>
            @error('readonly_mode') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </fieldset>

        {{-- ============== Mail delivery ============== --}}
        <fieldset class="pt-4 border-t border-stone-100 dark:border-stone-800"
                  x-data="{ mailOn: {{ $mailEnabled ? 'true' : 'false' }} }"
                  data-testid="mail-fieldset">
            <legend class="text-lg font-semibold text-stone-900 dark:text-stone-100">Mail delivery</legend>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">Pause outgoing notification emails. The in-app notification bell + audit trails keep working — only SMTP delivery is suppressed.</p>

            {{-- Master switch --}}
            <label class="mt-4 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-950 transition-colors">
                <input type="hidden" name="mail_enabled" value="0">
                <input type="checkbox" name="mail_enabled" id="mail_enabled" value="1"
                       @checked($mailEnabled)
                       x-model="mailOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="mail-enabled-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Send emails</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Master switch. When off, every notification's mail channel is skipped — the database channel and admin-side flash messages still work.
                        Currently <strong x-text="mailOn ? 'enabled' : 'disabled'">{{ $mailEnabled ? 'enabled' : 'disabled' }}</strong>.
                    </span>
                </span>
            </label>
            @error('mail_enabled') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

            {{-- Per-module switches — disabled (visually) when master is off --}}
            <p class="mt-5 text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Per module</p>

            <label class="mt-2 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 transition-colors cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-950"
                   :class="{ 'opacity-50 cursor-not-allowed': !mailOn }">
                <input type="hidden" name="mail_mango_orchard_enabled" value="0">
                <input type="checkbox" name="mail_mango_orchard_enabled" id="mail_mango_orchard_enabled" value="1"
                       @checked($mailMangoOrchardEnabled)
                       :disabled="!mailOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="mail-mango-orchard-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Mango Orchard emails</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Seasonal "variety in season" alerts and the monthly newsletter. When off, the Newsletter Send button is refused at the controller — drafts stay safe.
                    </span>
                </span>
            </label>
            @error('mail_mango_orchard_enabled') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

            <label class="mt-3 flex items-start gap-3 p-4 rounded-xl border border-stone-200 dark:border-stone-700 transition-colors cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 dark:has-[:checked]:bg-orange-950"
                   :class="{ 'opacity-50 cursor-not-allowed': !mailOn }">
                <input type="hidden" name="mail_scheme_monitoring_enabled" value="0">
                <input type="checkbox" name="mail_scheme_monitoring_enabled" id="mail_scheme_monitoring_enabled" value="1"
                       @checked($mailSchemeMonitoringEnabled)
                       :disabled="!mailOn"
                       class="mt-1 rounded text-orange-500 focus:ring-orange-400"
                       data-testid="mail-scheme-monitoring-toggle">
                <span>
                    <span class="block font-medium text-stone-900 dark:text-stone-100">Pragati Darpan emails</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Task status / update / deadline reminder emails. In-app notifications continue.
                    </span>
                </span>
            </label>
            @error('mail_scheme_monitoring_enabled') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </fieldset>

        <div class="flex items-center gap-3 pt-4 border-t border-stone-100 dark:border-stone-800">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                Save settings
            </button>
        </div>
    </form>
</x-admin-layout>

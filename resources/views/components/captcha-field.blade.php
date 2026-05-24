@php
    $settings = app(\App\Settings\Settings::class);
    $captcha = app(\App\Captcha\Captcha::class);
@endphp

@if ($settings->captchaEnabled())
    @php $payload = $captcha->imagePayload(); @endphp

    <div data-testid="captcha-field" {{ $attributes }}>
        <x-input-label for="captcha" :value="__('Captcha')" />

        <div class="mt-1 flex items-center gap-3">
            <img src="{{ $payload['src'] }}" alt="Captcha challenge" data-testid="captcha-image"
                 class="rounded-lg border border-stone-200 bg-white">
            @unless ($payload['prefill'])
                <a href="javascript:void(0)"
                   onclick="this.previousElementSibling.src = this.dataset.refresh + '?' + Date.now()"
                   data-refresh="{{ $payload['src'] }}"
                   class="text-xs text-stone-500 hover:text-stone-900">Refresh</a>
            @endunless
        </div>

        @if ($payload['prefill'] !== null)
            <p class="mt-2 text-xs text-amber-700 bg-amber-100 border border-amber-200 rounded-lg px-3 py-2"
               data-testid="captcha-autosolve-hint">
                Autosolve is on — the captcha field is prefilled with the correct answer.
            </p>
        @endif

        <x-text-input
            id="captcha"
            name="{{ \App\Captcha\Captcha::FIELD }}"
            type="text"
            inputmode="text"
            autocomplete="off"
            required
            value="{{ $payload['prefill'] ?? '' }}"
            class="mt-2 block w-full"
            placeholder="Type the characters above"
            data-testid="captcha-input"
        />
        <x-input-error class="mt-2" :messages="$errors->get(\App\Captcha\Captcha::FIELD)" />
    </div>
@endif

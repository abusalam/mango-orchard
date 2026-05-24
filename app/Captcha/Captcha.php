<?php

declare(strict_types=1);

namespace App\Captcha;

use App\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Mews\Captcha\Captcha as MewsCaptcha;

class Captcha
{
    public const string FIELD = 'captcha';

    public function __construct(
        private readonly MewsCaptcha $mews,
        private readonly Settings $settings,
    ) {}

    /**
     * Generate (or reference) the captcha image. When autosolve is enabled the
     * image is generated inline as a data URI and the plaintext answer is
     * returned in `prefill` so the field can be pre-filled in the UI; the
     * server still validates normally — the prefilled answer is the correct one.
     *
     * @return array{src: string, prefill: ?string}
     */
    public function imagePayload(): array
    {
        if (! $this->settings->captchaAutosolve()) {
            return [
                'src' => $this->mews->src('default'),
                'prefill' => null,
            ];
        }

        $result = $this->mews->create('default', api: true);
        $cached = Cache::get('captcha_'.md5($result['key']));

        return [
            'src' => $result['img'],
            'prefill' => is_array($cached) ? implode('', $cached) : $cached,
        ];
    }

    public function verify(?string $answer): bool
    {
        if ($answer === null) {
            return false;
        }

        return $this->mews->check($answer);
    }
}

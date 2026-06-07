<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Notifications;

use App\Mail\EmailTemplateRenderer;
use App\Modules\MangoOrchard\Models\MangoVariety;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fired by `mango:dispatch-seasonal-alerts` when a variety enters its
 * peak season. Sent only to users who opted in via the
 * `notify_seasonal` onboarding preference (or who later flipped it on
 * from their profile).
 *
 * Body and subject come from the editable `mango.variety_in_season`
 * email template so admins can adjust wording without a deploy.
 */
class VarietyInSeason extends Notification implements ShouldQueue
{
    use Queueable;

    public const TEMPLATE_KEY = 'mango.variety_in_season';

    public function __construct(public MangoVariety $variety) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        $channels = ['database'];
        if (app(\App\Settings\Settings::class)->mailEnabledForMangoOrchard()) {
            array_unshift($channels, 'mail');
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vars = [
            'variety_name' => $this->variety->name,
            'variety_origin' => $this->variety->origin ?? '—',
            'season_window' => $this->formatWindow($this->variety),
            'variety_url' => url(route('varieties.show', $this->variety)),
        ];

        $msg = EmailTemplateRenderer::render(self::TEMPLATE_KEY, $vars, $notifiable->name)
            ?? $this->fallbackMail($notifiable->name, $vars);

        return $msg->action('See the variety', $vars['variety_url']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => 'mango.variety_in_season',
            'variety_id' => $this->variety->id,
            'variety_name' => $this->variety->name,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function availablePlaceholders(): array
    {
        return [
            'variety_name' => 'Name of the variety just hitting season.',
            'variety_origin' => 'Origin region (e.g. "Malda, West Bengal").',
            'season_window' => 'Human-readable season window — e.g. "May to July".',
            'variety_url' => 'Direct link to the variety detail page.',
        ];
    }

    /**
     * Builds "May to July" / "August only" etc from start/end month ints.
     */
    private function formatWindow(MangoVariety $variety): string
    {
        if (! $variety->season_start) {
            return '—';
        }
        $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $start = $months[$variety->season_start] ?? '';
        if (! $variety->season_end || $variety->season_end === $variety->season_start) {
            return "{$start} only";
        }
        $end = $months[$variety->season_end] ?? '';

        return "{$start} to {$end}";
    }

    /**
     * @param  array<string, scalar>  $vars
     */
    private function fallbackMail(string $recipientName, array $vars): MailMessage
    {
        return (new MailMessage())
            ->subject("{$vars['variety_name']} is in season")
            ->greeting("Hi {$recipientName},")
            ->line("{$vars['variety_name']} from {$vars['variety_origin']} is hitting peak — its window is {$vars['season_window']}.")
            ->line('Open the variety page below for the full profile.');
    }
}

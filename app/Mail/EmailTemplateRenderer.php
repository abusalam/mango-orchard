<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Renders a DB-backed {@see EmailTemplate} into a MailMessage. Notifications
 * call {@see render()} with their key, the variable map, and the recipient
 * — and get back a fully wired-up MailMessage that they can further
 * decorate (attach files, add action buttons, mark error/success).
 *
 * Substitution syntax is `{placeholder_name}`. Unknown placeholders are
 * left untouched so authors notice their typos in the rendered email
 * rather than getting empty strings.
 *
 * If a template row is missing, render() returns null — callers should
 * fall back to a hard-coded MailMessage so a missing template doesn't
 * fail the queue job.
 */
class EmailTemplateRenderer
{
    /**
     * @param  array<string, scalar|\Stringable|null>  $vars
     */
    public static function render(string $key, array $vars, string $recipientName): ?MailMessage
    {
        $template = EmailTemplate::forKey($key);
        if ($template === null) {
            return null;
        }

        return self::renderInline($template->subject, $template->body, $vars, $recipientName);
    }

    /**
     * Same as {@see render()} but takes raw subject/body strings instead
     * of looking up a DB row. Used by the admin preview endpoint so
     * unsaved form values can be previewed without hitting the database.
     *
     * @param  array<string, scalar|\Stringable|null>  $vars
     */
    public static function renderInline(string $subject, string $body, array $vars, string $recipientName): MailMessage
    {
        $msg = (new MailMessage())
            ->subject(self::substitute($subject, $vars))
            ->greeting("Hi {$recipientName},");

        // Split body on blank lines so each paragraph becomes its own
        // `->line()` call. Markdown formatting within a paragraph
        // (**bold**, [links](...)) is honoured by MailMessage's renderer.
        foreach (preg_split('/\R{2,}/', trim(self::substitute($body, $vars))) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $msg->line($paragraph);
            }
        }

        return $msg;
    }

    /**
     * @param  array<string, scalar|\Stringable|null>  $vars
     */
    private static function substitute(string $template, array $vars): string
    {
        return preg_replace_callback('/\{([a-z0-9_]+)\}/i', function ($m) use ($vars) {
            $key = $m[1];

            return array_key_exists($key, $vars)
                ? (string) ($vars[$key] ?? '')
                : $m[0]; // leave literal {unknown} so the typo is visible
        }, $template) ?? $template;
    }
}

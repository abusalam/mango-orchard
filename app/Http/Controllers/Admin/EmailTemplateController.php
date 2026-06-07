<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailTemplateRenderer;
use App\Models\EmailTemplate;
use App\Modules\MangoOrchard\Notifications\VarietyInSeason;
use App\Modules\SchemeMonitoring\Notifications\TaskDeadlineReminder;
use App\Modules\SchemeMonitoring\Notifications\TaskStatusChanged;
use App\Modules\SchemeMonitoring\Notifications\TaskUpdated;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Markdown;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin CRUD-lite for editable email templates. Gated behind
 * `monitoring.manage` because every current template carries scheme-
 * monitoring copy (task status changes, deadline reminders) — the link
 * lives in the Pragati Darpan sidebar group accordingly. If another
 * module adds its own templates later, factor a per-module permission
 * filter onto the index query and split the route into per-module
 * sub-pages.
 *
 * Read-only list + per-row edit form. Placeholders are documented on the
 * edit form from the source-of-truth `availablePlaceholders()` method on
 * each notification class — so adding a new placeholder to the code
 * automatically updates the UI docs, no DB migration needed.
 */
class EmailTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_MANAGE])];
    }

    public function index(): View
    {
        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function edit(EmailTemplate $template): View
    {
        return view('admin.email-templates.edit', [
            'template' => $template,
            'placeholders' => $this->placeholdersFor($template->key),
        ]);
    }

    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $template->update($data);

        return redirect()
            ->route('admin.email-templates.index')
            ->with('status', "Template '{$template->name}' updated.");
    }

    /**
     * Render the template as a real HTML email using sample data so the
     * admin can see what subscribers will get. Accepts subject + body
     * from the form so unsaved edits can be previewed without saving.
     * If neither is supplied (e.g. opening preview from the index page)
     * the saved values are used.
     */
    public function preview(Request $request, EmailTemplate $template): Response
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        // Render with whatever the admin is currently looking at —
        // POSTed form values win over the saved DB row.
        $subject = $data['subject'] ?? $template->subject;
        $body = $data['body'] ?? $template->body;

        $vars = $this->sampleVarsFor($template->key);
        $rendered = EmailTemplateRenderer::renderInline($subject, $body, $vars, 'Anjali Sen');

        // Add the same structural extras the live notification would add
        // — action button + a couple of attachment lines — so the preview
        // matches what recipients actually see, not just the template text.
        $rendered->action('Open task', url('/'));
        $rendered->line('**Attachments:**');
        $rendered->line('- [inspection-report.pdf](#) — 124 KB');
        $rendered->line('- [site-photo.jpg](#) — 87 KB');

        if (str_ends_with($template->key, '.overdue')) {
            $rendered->error();
        }

        $html = app(Markdown::class)->render('notifications::email', $rendered->data() + [
            'subject' => $rendered->subject,
            'level' => $rendered->level,
            'greeting' => $rendered->greeting,
            'salutation' => $rendered->salutation,
            'introLines' => $rendered->introLines,
            'outroLines' => $rendered->outroLines,
            'actionText' => $rendered->actionText,
            'actionUrl' => $rendered->actionUrl,
            'displayableActionUrl' => $rendered->actionUrl,
        ]);

        return response(
            $this->wrapPreview($template, (string) $html, $rendered->subject),
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }

    /**
     * Wrap the rendered email HTML in a thin admin chrome — preview banner
     * at the top, the rendered email body underneath. Banner explains
     * this is a preview using sample data.
     */
    private function wrapPreview(EmailTemplate $template, string $emailHtml, string $renderedSubject): string
    {
        $name = e($template->name);
        $key = e($template->key);
        $subject = e($renderedSubject);

        $banner = <<<HTML
<div style="background: #fef3c7; border-bottom: 1px solid #f59e0b; padding: 12px 24px; font-family: -apple-system, system-ui, sans-serif; font-size: 13px; color: #78350f;">
    <strong>Preview · sample data</strong> · {$name} (<code>{$key}</code>)<br>
    <span style="opacity:0.8;">Rendered subject: {$subject}</span>
</div>
HTML;

        return $banner.$emailHtml;
    }

    /**
     * Realistic dummy values for each template's placeholders so the
     * preview reads like a real email, not lorem ipsum.
     *
     * @return array<string, scalar|\Stringable>
     */
    private function sampleVarsFor(string $key): array
    {
        $common = [
            'task_title' => 'Inspect block water tank — Mothabari',
            'scheme_name' => 'Drinking Water Programme',
            'deadline' => '15 Jul 2026',
            'actor_name' => 'Anjali Sen (District Officer)',
        ];

        return match (true) {
            $key === 'task.status_changed' => $common + [
                'old_status' => 'Pending',
                'new_status' => 'In progress',
            ],
            $key === 'task.deadline_reminder.t-7' => $common + ['days_until' => '7'],
            $key === 'task.deadline_reminder.t-3' => $common + ['days_until' => '3'],
            $key === 'task.deadline_reminder.t-1' => $common + ['days_until' => '1'],
            $key === 'task.deadline_reminder.overdue' => $common + ['days_until' => ''],
            $key === 'mango.variety_in_season' => [
                'variety_name' => 'Alphonso',
                'variety_origin' => 'Ratnagiri, Maharashtra',
                'season_window' => 'April to June',
                'variety_url' => url('/varieties/alphonso'),
            ],
            default => $common,
        };
    }

    /**
     * Resolve which notification class owns this template key and ask it
     * for the placeholder list. Unknown keys → empty list (UI hides the
     * docs panel cleanly).
     *
     * @return array<string, string>
     */
    private function placeholdersFor(string $key): array
    {
        return match (true) {
            $key === TaskStatusChanged::TEMPLATE_KEY => TaskStatusChanged::availablePlaceholders(),
            $key === TaskUpdated::TEMPLATE_KEY => TaskUpdated::availablePlaceholders(),
            str_starts_with($key, 'task.deadline_reminder.') => TaskDeadlineReminder::availablePlaceholders(),
            $key === VarietyInSeason::TEMPLATE_KEY => VarietyInSeason::availablePlaceholders(),
            default => [],
        };
    }
}

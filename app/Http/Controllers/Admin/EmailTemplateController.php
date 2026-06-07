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
 * Admin CRUD-lite for editable email templates. Open to anyone with
 * `settings.manage`, `monitoring.manage`, or `varieties.manage`, but
 * each holder only sees / can touch templates from THEIR module:
 *
 *   - settings.manage  →  every template (sysadmin / superuser)
 *   - monitoring.manage →  task.* only (Niyantrak — Pragati Darpan)
 *   - varieties.manage  →  mango.* only (Curator — Mango Orchard)
 *
 * Module-scoping lives in {@see canTouch()} and is applied at the
 * middleware (permission gate), the index query, and each per-template
 * action. The page itself groups templates by module for clarity (see
 * {@see moduleFor()}); the link lives under General admin in the
 * sidebar so anyone with one of the three permissions can reach it.
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
        // Any of the three permissions opens the page; canTouch() then
        // filters what the holder can actually see / edit per-row.
        $any = Permissions::SETTINGS_MANAGE.'|'.Permissions::MONITORING_MANAGE.'|'.Permissions::VARIETIES_MANAGE;

        return [new Middleware(['auth', 'permission:'.$any])];
    }

    public function index(Request $request): View
    {
        $viewer = $request->user();

        // Pull every template the viewer is allowed to touch — then
        // group by module so the page reads as "here's what each
        // module sends" rather than one undifferentiated table.
        $grouped = EmailTemplate::orderBy('name')->get()
            ->filter(fn (EmailTemplate $t) => $this->canTouch($viewer, $t))
            ->groupBy(fn (EmailTemplate $t) => $this->moduleFor($t->key));

        // Stable display order — Pragati Darpan first (larger group),
        // then Mango Orchard, then anything that hasn't been mapped.
        $order = ['Pragati Darpan', 'Mango Orchard', 'Other'];
        $groups = collect($order)
            ->mapWithKeys(fn (string $label) => [$label => $grouped->get($label, collect())])
            ->filter(fn ($collection) => $collection->isNotEmpty());

        return view('admin.email-templates.index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Map a template key to its human-readable module label. New module
     * templates should add their prefix here (the controller already
     * resolves placeholders by prefix in {@see placeholdersFor()} and
     * scopes access by prefix in {@see canTouch()} — keep all three
     * maps in lockstep).
     */
    private function moduleFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'task.') => 'Pragati Darpan',
            str_starts_with($key, 'mango.') => 'Mango Orchard',
            default => 'Other',
        };
    }

    /**
     * Can this viewer touch this specific template?
     *
     *   - settings.manage holders → yes, everything (sysadmin escape hatch)
     *   - monitoring.manage holders → only task.* templates
     *   - varieties.manage holders → only mango.* templates
     *
     * "Other" templates (anything that doesn't match a known prefix)
     * are gated to settings.manage only so an unmapped notification
     * can't be edited by a module admin who shouldn't own it.
     */
    private function canTouch(\App\Models\User $viewer, EmailTemplate $template): bool
    {
        if ($viewer->can(Permissions::SETTINGS_MANAGE)) {
            return true;
        }

        if (str_starts_with($template->key, 'task.')) {
            return $viewer->can(Permissions::MONITORING_MANAGE);
        }

        if (str_starts_with($template->key, 'mango.')) {
            return $viewer->can(Permissions::VARIETIES_MANAGE);
        }

        return false;
    }

    public function edit(Request $request, EmailTemplate $template): View
    {
        abort_unless($this->canTouch($request->user(), $template), 403);

        return view('admin.email-templates.edit', [
            'template' => $template,
            'placeholders' => $this->placeholdersFor($template->key),
        ]);
    }

    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        abort_unless($this->canTouch($request->user(), $template), 403);

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
        abort_unless($this->canTouch($request->user(), $template), 403);

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

        // Add the same structural extras the live notification would
        // tack on, dispatched by template key — task.* gets an attachment
        // block + "Open task" button; mango.variety_in_season gets the
        // "See the variety" button; new templates can declare their own
        // shape here in lockstep with their notification class.
        $this->decorateForPreview($template->key, $rendered, $vars);

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
    /**
     * Tack on the action button, attachment list, and `level` colouring
     * that each notification's `toMail()` would add. Keeps the preview
     * truthful — admins see EXACTLY the shape recipients get, not a
     * task-flavoured stand-in for every template.
     *
     * @param  array<string, scalar|\Stringable>  $vars
     */
    private function decorateForPreview(string $key, \Illuminate\Notifications\Messages\MailMessage $msg, array $vars): void
    {
        switch (true) {
            case $key === TaskStatusChanged::TEMPLATE_KEY:
            case $key === TaskUpdated::TEMPLATE_KEY:
            case str_starts_with($key, 'task.deadline_reminder.'):
                $msg->line('**Attachments:**');
                $msg->line('- [inspection-report.pdf](#) — 124 KB');
                $msg->line('- [site-photo.jpg](#) — 87 KB');
                $msg->action('Open task', url('/'));
                if (str_ends_with($key, '.overdue')) {
                    $msg->error();
                }

                return;

            case $key === VarietyInSeason::TEMPLATE_KEY:
                $msg->action('See the variety', (string) ($vars['variety_url'] ?? url('/')));

                return;

            default:
                // Unknown template — render the body alone with no
                // structural extras rather than tacking on something
                // misleading. Add a case above when a new notification
                // declares its own shape.
                return;
        }
    }

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

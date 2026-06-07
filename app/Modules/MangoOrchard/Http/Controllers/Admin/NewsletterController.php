<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\MangoOrchard\Models\NewsletterIssue;
use App\Modules\MangoOrchard\Notifications\NewsletterIssued;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Admin CRUD for the Mango Orchard newsletter. Issues sit as drafts
 * until an admin clicks Send — at which point we queue {@see NewsletterIssued}
 * to every subscriber (notify+verified) and stamp the audit columns.
 *
 * Gated behind `varieties.manage` because newsletters carry orchard
 * content (cultivar profiles, tasting notes) and the curator role
 * already holds that permission. Easy to switch to a dedicated
 * `newsletters.manage` permission later if scope diverges.
 */
class NewsletterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::VARIETIES_MANAGE])];
    }

    public function index(): View
    {
        return view('admin.mango-orchard.newsletter.index', [
            'drafts' => NewsletterIssue::drafts()->with('author')->latest()->get(),
            'sentIssues' => NewsletterIssue::sent()->with('author')->limit(50)->get(),
            'subscriberCount' => $this->subscriberQuery()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.mango-orchard.newsletter.edit', [
            'issue' => new NewsletterIssue(),
            'subscriberCount' => $this->subscriberQuery()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $newsletter = NewsletterIssue::create($data + ['created_by' => $request->user()->id]);

        return redirect()
            ->route('admin.mango-orchard.newsletter.edit', $newsletter)
            ->with('status', 'Draft saved.');
    }

    public function edit(NewsletterIssue $newsletter): View
    {
        abort_unless($newsletter->isDraft(), 403, 'Sent issues are read-only.');

        return view('admin.mango-orchard.newsletter.edit', [
            'issue' => $newsletter,
            'subscriberCount' => $this->subscriberQuery()->count(),
        ]);
    }

    public function update(Request $request, NewsletterIssue $newsletter): RedirectResponse
    {
        abort_unless($newsletter->isDraft(), 403, 'Sent issues are read-only.');

        $newsletter->update($this->validated($request));

        return back()->with('status', 'Draft updated.');
    }

    public function send(NewsletterIssue $newsletter): RedirectResponse
    {
        abort_unless($newsletter->isDraft(), 409, 'Issue already sent.');

        // Mail-only flow — refuse outright if the master or per-module
        // mail switch is off so admins don't think the issue went out
        // when it actually no-op'd in the queue.
        if (! app(\App\Settings\Settings::class)->mailEnabledForMangoOrchard()) {
            return back()->withErrors(['send' => 'Email sending is disabled in Settings — re-enable Mango Orchard mail to send.']);
        }

        $subscribers = $this->subscriberQuery()->get();
        $count = $subscribers->count();

        if ($count === 0) {
            return back()->withErrors(['send' => 'No opted-in subscribers — nothing to send.']);
        }

        Notification::send($subscribers, new NewsletterIssued($newsletter));

        $newsletter->update([
            'sent_at' => now(),
            'sent_to_count' => $count,
        ]);

        return redirect()
            ->route('admin.mango-orchard.newsletter.index')
            ->with('status', "Newsletter queued to {$count} subscriber(s).");
    }

    public function destroy(NewsletterIssue $newsletter): RedirectResponse
    {
        abort_unless($newsletter->isDraft(), 403, 'Sent issues can\'t be deleted (kept for audit).');

        $newsletter->delete();

        return redirect()
            ->route('admin.mango-orchard.newsletter.index')
            ->with('status', 'Draft deleted.');
    }

    /**
     * Single source of truth for the recipient set: subscribed +
     * email-verified. Lives here so the count on the admin UI and
     * the actual send target can't drift.
     */
    private function subscriberQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->where('subscribe_newsletter', true)
            ->whereNotNull('email_verified_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
        ]);
    }
}

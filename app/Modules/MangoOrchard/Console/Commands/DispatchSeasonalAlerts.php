<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Console\Commands;

use App\Models\User;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Modules\MangoOrchard\Notifications\VarietyInSeason;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily pass that fires the VarietyInSeason notification on the first
 * day of each variety's `season_start` month. Recipients: every user
 * with `notify_seasonal = true` AND a verified email. The notification
 * is queued so this command is fast even with thousands of subscribers.
 *
 * Runs from `mango:dispatch-seasonal-alerts` — scheduled at 06:30 by
 * MangoOrchardServiceProvider.
 */
class DispatchSeasonalAlerts extends Command
{
    protected $signature = 'mango:dispatch-seasonal-alerts {--dry-run : Print counts without sending notifications}';

    protected $description = 'Send "variety in season" alerts on the first day of each variety\'s peak month.';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $dryRun = (bool) $this->option('dry-run');

        // Only fire on day 1 of any month — keeps the alert a single
        // "season started" ping, not a daily nag.
        if ($today->day !== 1) {
            $this->info('Not the first of the month — nothing to dispatch.');

            return self::SUCCESS;
        }

        $varieties = MangoVariety::query()
            ->where('season_start', $today->month)
            ->get();

        if ($varieties->isEmpty()) {
            $this->info("No varieties starting season in month {$today->month}.");

            return self::SUCCESS;
        }

        $subscribers = User::query()
            ->where('notify_seasonal', true)
            ->whereNotNull('email_verified_at')
            ->get();

        if ($subscribers->isEmpty()) {
            $this->info('No opted-in subscribers — nothing to dispatch.');

            return self::SUCCESS;
        }

        $varietyCount = $varieties->count();
        $subscriberCount = $subscribers->count();

        $this->info("Dispatching {$varietyCount} variety alert(s) to {$subscriberCount} subscriber(s).");

        if ($dryRun) {
            foreach ($varieties as $v) {
                $this->line("  [dry-run] {$v->name} → {$subscriberCount} recipient(s)");
            }

            return self::SUCCESS;
        }

        foreach ($varieties as $variety) {
            Notification::send($subscribers, new VarietyInSeason($variety));
        }

        $this->info('Dispatched.');

        return self::SUCCESS;
    }
}

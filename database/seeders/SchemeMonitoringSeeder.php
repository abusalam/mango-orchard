<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Attachment;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Telemetry\Telemetry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data for the Pragati Darpan (scheme monitoring) module.
 *
 * Seeds:
 *   - A 3-level designation hierarchy (District → Block → Field)
 *   - One Samikshak user per designation + MonitorProfile enrolment
 *   - Two schemes (one mature, one fresh) tagged with abbreviations
 *   - A spread of tasks deliberately distributed across every deadline-bar
 *     bucket (early / on-track / warming / urgent / critical / due-today /
 *     overdue / completed / cancelled) so the dashboard demo shows the full
 *     colour ramp. Each task has a window of 3-60 days and a created_at
 *     chosen to land its progress in the target bucket.
 *
 * Skips itself if any monitoring data already exists — re-seeding the rest
 * of the database stays safe.
 */
class SchemeMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        if (Scheme::query()->exists() || MonitorProfile::query()->exists()) {
            // Idempotency guard: a partial seed already happened. Refuse to
            // double-up. Use migrate:fresh --seed for a clean slate.
            return;
        }

        Telemetry::withoutRecording(function (): void {
            [$district, $block, $field] = $this->seedDesignations();
            [$director, $lead, $officer] = $this->seedMonitors($district, $block, $field);
            [$mature, $fresh] = $this->seedSchemes($director);
            $tasks = $this->seedTasks($mature, $fresh, $director, $lead, $officer);
            $this->seedAttachments([$mature, $fresh], $tasks, $director);
        });
    }

    /**
     * District → Block → Field, parented through `parent_id` so the
     * Hierarchy walker resolves descendants correctly.
     *
     * @return array{0: Designation, 1: Designation, 2: Designation}
     */
    private function seedDesignations(): array
    {
        $district = Designation::create([
            'name' => 'District Officer',
            'description' => 'Top of the reporting chain — sees everything in their district.',
            'level' => 10,
            'parent_id' => null,
        ]);
        $block = Designation::create([
            'name' => 'Block Officer',
            'description' => 'Manages a block within a district.',
            'level' => 5,
            'parent_id' => $district->id,
        ]);
        $field = Designation::create([
            'name' => 'Field Officer',
            'description' => 'On-ground execution + reporting.',
            'level' => 1,
            'parent_id' => $block->id,
        ]);

        return [$district, $block, $field];
    }

    /**
     * One monitor per designation. Returns them in the same order as the
     * designations so the caller can assign tasks by seniority.
     *
     * @return array{0: User, 1: User, 2: User}
     */
    private function seedMonitors(Designation $district, Designation $block, Designation $field): array
    {
        $director = $this->createMonitor('Anjali Sen', 'anjali.sen@example.in', $district);
        $lead = $this->createMonitor('Rahim Bose', 'rahim.bose@example.in', $block);
        $officer = $this->createMonitor('Sumana Roy', 'sumana.roy@example.in', $field);

        return [$director, $lead, $officer];
    }

    private function createMonitor(string $name, string $email, Designation $designation): User
    {
        $user = User::factory()->monitor()->create([
            'name' => $name,
            'email' => $email,
        ]);
        $user->designations()->attach($designation->id);
        MonitorProfile::create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Two schemes: one started ~90 days ago so its umbrella runway is
     * partly burned, one freshly opened this week.
     *
     * @return array{0: Scheme, 1: Scheme}
     */
    private function seedSchemes(User $owner): array
    {
        $mature = Scheme::create([
            'name' => 'Drinking Water Programme',
            'abbreviation' => 'DWP',
            'description' => 'Block-level water-source surveys, repair, and re-commissioning.',
            'start_date' => now()->subDays(90)->toDateString(),
            'end_date' => now()->addDays(180)->toDateString(),
            'status' => Scheme::STATUS_ACTIVE,
            'owner_id' => $owner->id,
        ]);
        $fresh = Scheme::create([
            'name' => 'Soil Health Mission',
            'abbreviation' => 'SHM',
            'description' => 'Soil sample collection, lab reporting, and farmer advisories.',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(120)->toDateString(),
            'status' => Scheme::STATUS_ACTIVE,
            'owner_id' => $owner->id,
        ]);

        return [$mature, $fresh];
    }

    /**
     * Builds one task per spec entry — each entry pins the window length
     * and a `burn` fraction that decides created_at relative to today so
     * `progressPct = burn * 100` lands the bar in the target bucket. See
     * resources/views/components/scheme-monitoring/deadline-bar.blade.php
     * for the bucket cascade.
     */
    /**
     * @return list<Task>
     */
    private function seedTasks(Scheme $mature, Scheme $fresh, User $director, User $lead, User $officer): array
    {
        $assignees = [$director, $lead, $officer];
        $schemes = [$mature, $fresh];

        $specs = [
            // [windowDays, burn, status, priority, titleSuffix]
            // ── early (0-24%) ─────────────────────────────────────────
            [60, 0.05, Task::STATUS_PENDING, Task::PRIORITY_NORMAL, 'Stakeholder kickoff briefing'],
            [45, 0.15, Task::STATUS_PENDING, Task::PRIORITY_LOW, 'Block-level baseline survey'],
            [30, 0.20, Task::STATUS_IN_PROGRESS, Task::PRIORITY_NORMAL, 'Vendor shortlisting for repairs'],
            // ── on-track (25-49%) ─────────────────────────────────────
            [40, 0.30, Task::STATUS_IN_PROGRESS, Task::PRIORITY_NORMAL, 'Compile village-wise needs assessment'],
            [25, 0.40, Task::STATUS_IN_PROGRESS, Task::PRIORITY_NORMAL, 'Tender notification publication'],
            [50, 0.45, Task::STATUS_PENDING, Task::PRIORITY_NORMAL, 'Coordinate with district engineer'],
            // ── warming (50-74%) ──────────────────────────────────────
            [20, 0.55, Task::STATUS_IN_PROGRESS, Task::PRIORITY_HIGH, 'Mid-quarter progress report'],
            [14, 0.65, Task::STATUS_IN_PROGRESS, Task::PRIORITY_NORMAL, 'Verify partial-payment invoices'],
            // ── urgent (75-89%) ──────────────────────────────────────
            [10, 0.80, Task::STATUS_IN_PROGRESS, Task::PRIORITY_HIGH, 'Pre-monsoon site inspection'],
            [12, 0.75, Task::STATUS_PENDING, Task::PRIORITY_HIGH, 'Submit revised budget projection'],
            // ── critical (90-100%) ───────────────────────────────────
            [12, 0.95, Task::STATUS_IN_PROGRESS, Task::PRIORITY_URGENT, 'Final compliance certificate filing'],
            // ── due today (deadline == today) ────────────────────────
            [7, 1.00, Task::STATUS_IN_PROGRESS, Task::PRIORITY_URGENT, 'Monthly KPI submission'],
            // ── overdue (deadline in past) ────────────────────────────
            [5, 1.40, Task::STATUS_IN_PROGRESS, Task::PRIORITY_URGENT, 'Pending bill-of-quantity validation'],
            [10, 1.20, Task::STATUS_PENDING, Task::PRIORITY_HIGH, 'Field-officer training rollout'],
            // ── completed (snaps to 100%, green) ──────────────────────
            [20, 0.60, Task::STATUS_COMPLETED, Task::PRIORITY_NORMAL, 'Phase-1 audit report'],
            [3, 1.00, Task::STATUS_COMPLETED, Task::PRIORITY_LOW, 'Stakeholder sign-off acknowledgement'],
            // ── cancelled ─────────────────────────────────────────────
            [30, 0.40, Task::STATUS_CANCELLED, Task::PRIORITY_NORMAL, 'Discontinued vendor evaluation'],
        ];

        $tasks = [];
        foreach ($specs as $i => [$window, $burn, $status, $priority, $title]) {
            $createdDaysAgo = (int) round($window * $burn);
            $deadline = now()->subDays($createdDaysAgo)->addDays($window)->startOfDay();
            $createdAt = now()->subDays($createdDaysAgo)->startOfMinute();

            // Spread across assignees + schemes so the dashboard sidebar
            // and filter chips both have variety to display.
            $assignee = $assignees[$i % count($assignees)];
            $scheme = $schemes[$i % count($schemes)];

            $task = Task::create([
                'scheme_id' => $scheme->id,
                'title' => $title,
                'description' => "Auto-seeded for dashboard demo: {$title}.",
                'deadline' => $deadline->toDateString(),
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assignee->id,
                'created_by' => $director->id,
                'completed_at' => $status === Task::STATUS_COMPLETED ? $createdAt->copy()->addDay() : null,
            ]);

            // Tasks don't normally let you set created_at on create() — push
            // it back so the progress bar's anchor (task.created_at) lands
            // where the spec asks. Touching updated_at too keeps the
            // "latest activity" view sensible.
            $task->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            $tasks[] = $task;
        }

        return $tasks;
    }

    /**
     * Attach a couple of dummy files to each scheme and to roughly half
     * the tasks. Writes real placeholder blobs onto the `public` disk so
     * the dashboard chip-popovers link to URLs that actually serve
     * (instead of 404ing). Mix of MIME types so the file-icon hints are
     * varied.
     *
     * @param  list<Scheme>  $schemes
     * @param  list<Task>  $tasks
     */
    private function seedAttachments(array $schemes, array $tasks, User $uploader): void
    {
        // Two files per scheme — a brief PDF and a vendor-quote PDF.
        foreach ($schemes as $scheme) {
            $abbr = strtolower($scheme->abbreviation);
            $this->putAttachment(
                $scheme,
                $uploader,
                "{$abbr}-programme-brief.pdf",
                'application/pdf',
                $this->placeholderPdf("{$scheme->name} — Programme Brief"),
            );
            $this->putAttachment(
                $scheme,
                $uploader,
                "{$abbr}-vendor-quotes.pdf",
                'application/pdf',
                $this->placeholderPdf("{$scheme->name} — Vendor Quotes"),
            );
        }

        // Attach one file to every other task; rotate through plausible
        // file types so the chips look like real working files.
        $fileTypes = [
            ['ext' => 'pdf', 'mime' => 'application/pdf', 'kind' => 'inspection-report'],
            ['ext' => 'jpg', 'mime' => 'image/jpeg', 'kind' => 'site-photo'],
            ['ext' => 'png', 'mime' => 'image/png', 'kind' => 'screenshot'],
            ['ext' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'kind' => 'meeting-notes'],
        ];

        foreach ($tasks as $i => $task) {
            if ($i % 2 === 1) {
                continue; // skip half so non-attached tasks exist for contrast
            }
            $type = $fileTypes[$i % count($fileTypes)];
            $original = "{$type['kind']}-task-{$task->id}.{$type['ext']}";
            $this->putAttachment(
                $task,
                $uploader,
                $original,
                $type['mime'],
                "Placeholder for {$type['kind']} on task #{$task->id}.\n",
            );
        }
    }

    /**
     * Drop a file on the `public` disk and register an Attachment row
     * pointing at it. Keeps the path convention identical to what
     * AttachmentController does at runtime ("monitoring-attachments/<uuid>")
     * so the URL helper and delete cascade behave the same.
     */
    private function putAttachment(Model $attachable, User $uploader, string $originalName, string $mime, string $body): void
    {
        $path = 'monitoring-attachments/'.Str::uuid().'-'.$originalName;
        Storage::disk('public')->put($path, $body);

        Attachment::create([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'uploaded_by' => $uploader->id,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $mime,
            'size_bytes' => strlen($body),
        ]);
    }

    /**
     * Minimal but valid one-page PDF. Real-looking enough that `file --mime`
     * recognises it and a browser will preview it instead of erroring.
     */
    private function placeholderPdf(string $title): string
    {
        $escaped = str_replace(['(', ')'], ['\\(', '\\)'], $title);

        return implode("\n", [
            '%PDF-1.4',
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj',
            "4 0 obj << /Length 80 >> stream\nBT /F1 14 Tf 70 780 Td ({$escaped}) Tj ET\nendstream endobj",
            '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            'xref',
            '0 6',
            '0000000000 65535 f ',
            'trailer << /Size 6 /Root 1 0 R >>',
            'startxref',
            '0',
            '%%EOF',
        ]);
    }
}

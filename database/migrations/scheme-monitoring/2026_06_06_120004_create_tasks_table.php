<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scheme_id')->constrained('monitoring_schemes')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('deadline');
            $table->string('status')->default('pending')
                ->comment('pending | in_progress | completed | cancelled');
            $table->string('priority')->default('normal')
                ->comment('low | normal | high | urgent');
            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_overdue_reminder_at')->nullable()
                ->comment('Set by DispatchDeadlineReminders so we don\'t double-nag in a single day if the cron fires twice.');
            $table->timestamps();

            $table->index(['scheme_id', 'status']);
            $table->index(['assigned_to', 'status', 'deadline']);
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_tasks');
    }
};

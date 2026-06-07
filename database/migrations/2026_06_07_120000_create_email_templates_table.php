<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            // Stable identifier — referenced from notification classes.
            // Examples: task.status_changed, task.deadline_reminder.t-7.
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            // Templates use {placeholder_name} substitution. Available
            // placeholders are declared in the notification class via
            // availablePlaceholders() and surfaced in the admin UI.
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

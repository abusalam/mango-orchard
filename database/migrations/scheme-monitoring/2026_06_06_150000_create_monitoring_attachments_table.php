<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic attachments — one row per uploaded file linked to either
     * a `monitoring_schemes` or `monitoring_tasks` row via the standard
     * `attachable_type` / `attachable_id` pair.
     *
     * Cleanup of the underlying blob on disk + cascade-on-parent-delete are
     * handled in app code (see Attachment model's `deleting` hook and the
     * `booted()` hook on Task / Scheme) — `morphs()` has no foreign key
     * constraint to cascade through.
     */
    public function up(): void
    {
        Schema::create('monitoring_attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_attachments');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpcp_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mpcp_section_id')->constrained('mpcp_sections')->cascadeOnDelete();
            $table->json('data'); // {column_key: value} keyed against owning section's columns
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['mpcp_section_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcp_entries');
    }
};

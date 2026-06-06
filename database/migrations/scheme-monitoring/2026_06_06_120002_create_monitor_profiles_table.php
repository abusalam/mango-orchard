<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user enrolment in the scheme/project monitoring module. Holding
     * the `monitor` role is necessary but not sufficient — the user must
     * also have a row here so we know WHERE in the hierarchy they sit.
     *
     * `parent_user_id` is the user one rung above in the org chart.
     * NULL parent = top of the chain (sees the entire subtree underneath).
     */
    public function up(): void
    {
        Schema::create('monitoring_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('parent_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_profiles');
    }
};

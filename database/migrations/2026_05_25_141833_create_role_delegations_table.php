<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreignId('delegated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('delegated_at');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'role_id']);
            $table->index(['delegated_by']);
            $table->index(['revoked_at']);
        });

        // At most one *active* delegation per (recipient, role) — multiple
        // delegators can't compete to grant the same role to the same user.
        // Revoked rows are audit history and don't block re-delegation.
        DB::statement(
            'CREATE UNIQUE INDEX role_delegations_one_active_per_user_role
             ON role_delegations (user_id, role_id) WHERE revoked_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('role_delegations');
    }
};

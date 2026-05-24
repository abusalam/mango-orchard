<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'role_id']);
        });

        // A user may only have one *pending* application per role at a time.
        // Approved/rejected rows are history and don't block re-application.
        DB::statement(
            "CREATE UNIQUE INDEX role_applications_one_pending_per_user_role
             ON role_applications (user_id, role_id) WHERE status = 'pending'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('role_applications');
    }
};

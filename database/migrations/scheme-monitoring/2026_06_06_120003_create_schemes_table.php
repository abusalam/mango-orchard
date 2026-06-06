<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_schemes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete()
                ->comment('Monitor responsible for the overall scheme; their subtree determines visibility.');
            $table->string('status')->default('active')
                ->comment('active | paused | completed | archived');
            $table->timestamps();

            $table->index(['status', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_schemes');
    }
};

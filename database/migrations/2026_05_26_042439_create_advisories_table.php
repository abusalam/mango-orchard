<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisories', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('body');
            $table->string('category', 32)->index();   // seasonal | best_practice | pest_alert
            $table->string('severity', 16)->index();   // info | warning | urgent
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('published')->default(false)->index();
            $table->timestamps();
        });

        // Pivot: advisories ↔ varieties. An advisory with NO pivot rows
        // applies to every variety (general guidance); one or more rows
        // means it's targeted to those specific varieties.
        Schema::create('advisory_variety', function (Blueprint $table) {
            $table->foreignId('advisory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mango_variety_id')->constrained()->cascadeOnDelete();
            $table->primary(['advisory_id', 'mango_variety_id']);
            $table->index('mango_variety_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisory_variety');
        Schema::dropIfExists('advisories');
    }
};

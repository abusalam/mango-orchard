<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpcp_sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_bn')->nullable();
            $table->text('intro_md_en')->nullable();
            $table->text('intro_md_bn')->nullable();
            $table->string('layout', 16)->default('table'); // 'table' | 'card'
            $table->json('columns'); // [{key, label_en, label_bn, type}]
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['published', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcp_sections');
    }
};

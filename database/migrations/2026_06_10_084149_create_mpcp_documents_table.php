<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpcp_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_bn')->nullable();
            $table->text('attribution_md_en')->nullable();
            $table->text('attribution_md_bn')->nullable();
            $table->text('about_md_en')->nullable();
            $table->text('about_md_bn')->nullable();
            $table->text('footer_md_en')->nullable();
            $table->text('footer_md_bn')->nullable();
            $table->string('website_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcp_documents');
    }
};

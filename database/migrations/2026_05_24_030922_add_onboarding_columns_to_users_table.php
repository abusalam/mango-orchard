<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('region')->nullable();
            $table->string('expertise')->nullable();
            $table->foreignId('favorite_variety_id')->nullable()->constrained('mango_varieties')->nullOnDelete();
            $table->boolean('notify_seasonal')->default(false);
            $table->boolean('subscribe_newsletter')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('favorite_variety_id');
            $table->dropColumn([
                'region',
                'expertise',
                'notify_seasonal',
                'subscribe_newsletter',
                'onboarding_completed_at',
            ]);
        });
    }
};

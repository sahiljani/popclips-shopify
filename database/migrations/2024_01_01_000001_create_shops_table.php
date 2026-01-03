<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shopify_domain')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('access_token')->nullable();
            $table->json('scopes')->nullable();
            $table->enum('plan', ['free', 'pro'])->default('free');
            $table->timestamp('plan_started_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('shopify_domain');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};

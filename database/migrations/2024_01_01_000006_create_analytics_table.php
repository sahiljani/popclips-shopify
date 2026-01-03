<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('clip_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('hotspot_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('event_type', [
                'clip_view',
                'clip_complete',
                'hotspot_click',
                'add_to_cart',
                'purchase',
                'like',
                'share'
            ]);
            $table->string('session_id')->nullable();
            $table->string('visitor_id')->nullable();
            $table->string('shopify_product_id')->nullable();
            $table->decimal('revenue', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('country', 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['shop_id', 'event_type', 'created_at']);
            $table->index(['clip_id', 'event_type']);
            $table->index(['hotspot_id', 'event_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};

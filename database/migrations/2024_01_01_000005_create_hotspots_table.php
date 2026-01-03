<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clip_id')->constrained()->onDelete('cascade');
            $table->string('shopify_product_id');
            $table->string('product_title');
            $table->string('product_handle')->nullable();
            $table->string('product_image')->nullable();
            $table->decimal('product_price', 10, 2)->nullable();
            $table->string('product_currency', 3)->default('USD');
            $table->decimal('position_x', 5, 2)->comment('Percentage from left (0-100)');
            $table->decimal('position_y', 5, 2)->comment('Percentage from top (0-100)');
            $table->decimal('start_time', 8, 2)->comment('Start time in seconds');
            $table->decimal('end_time', 8, 2)->comment('End time in seconds');
            $table->enum('animation_style', ['pulse', 'static', 'bounce'])->default('pulse');
            $table->integer('clicks_count')->default(0);
            $table->integer('add_to_cart_count')->default(0);
            $table->timestamps();

            $table->index(['clip_id', 'start_time']);
            $table->index('shopify_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};

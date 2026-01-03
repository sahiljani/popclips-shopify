<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carousels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['standard', 'custom'])->default('standard');
            $table->enum('display_location', ['homepage', 'collection', 'product', 'all'])->default('homepage');
            $table->boolean('is_active')->default(true);
            $table->boolean('autoplay')->default(true);
            $table->integer('autoplay_speed')->default(5)->comment('Autoplay interval in seconds');
            $table->boolean('show_metrics')->default(true);
            $table->integer('items_desktop')->default(4);
            $table->integer('items_tablet')->default(3);
            $table->integer('items_mobile')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'type']);
            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carousels');
    }
};

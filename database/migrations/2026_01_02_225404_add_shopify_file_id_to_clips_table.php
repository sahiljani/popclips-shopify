<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clips', function (Blueprint $table) {
            $table->string('shopify_file_id')->nullable()->after('cloudinary_public_id');
            $table->index(['shop_id', 'shopify_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clips', function (Blueprint $table) {
            $table->dropIndex('clips_shop_id_shopify_file_id_index');
            $table->dropColumn('shopify_file_id');
        });
    }
};

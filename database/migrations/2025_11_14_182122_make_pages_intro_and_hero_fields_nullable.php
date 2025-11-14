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
        Schema::table('pages', function (Blueprint $table) {
            $table->json('intro')->nullable()->change();
            $table->json('hero_image_copyright')->nullable()->change();
            $table->json('hero_image_title')->nullable()->change();
            $table->json('slug')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('intro')->nullable(false)->change();
            $table->json('hero_image_copyright')->nullable(false)->change();
            $table->json('hero_image_title')->nullable(false)->change();
            $table->json('slug')->nullable(false)->change();
        });
    }
};

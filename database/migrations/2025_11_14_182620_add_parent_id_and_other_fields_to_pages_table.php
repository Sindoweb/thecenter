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
            $table->unsignedBigInteger('parent_id')->nullable()->after('author_id');
            $table->foreign('parent_id')
                ->references('id')
                ->on('pages')
                ->onDelete('set null');
            $table->boolean('menu')->default(false)->after('parent_id');
            $table->integer('sort')->default(0)->after('menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'menu', 'sort']);
        });
    }
};

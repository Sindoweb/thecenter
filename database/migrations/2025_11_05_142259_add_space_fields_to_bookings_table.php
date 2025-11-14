<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('customer_id')->constrained()->onDelete('restrict');
            $table->string('duration_type')->nullable()->after('booking_type');
            $table->decimal('price', 10, 2)->nullable()->after('duration_type');

            $table->index('space_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['space_id']);
            $table->dropColumn(['space_id', 'duration_type', 'price']);
        });
    }
};

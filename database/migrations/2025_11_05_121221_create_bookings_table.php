<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('booking_type');
            $table->string('status')->default('pending');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('total_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2);
            $table->integer('number_of_people');
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('mollie_payment_id')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('booking_type');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['start_date', 'end_date']);
            $table->index('mollie_payment_id');
        });
    }
};

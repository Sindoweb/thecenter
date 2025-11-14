<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('mollie_subscription_id')->unique();
            $table->string('mollie_customer_id');
            $table->string('booking_type');
            $table->string('duration_type');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('mollie_subscription_id');
            $table->index('mollie_customer_id');
            $table->index('booking_type');
            $table->index(['starts_at', 'ends_at']);
        });
    }
};

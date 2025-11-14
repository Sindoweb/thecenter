<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->string('booking_type');
            $table->string('duration_type');
            $table->decimal('price', 10, 2);
            $table->integer('min_people')->nullable();
            $table->integer('max_people')->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index('space_id');
            $table->index('booking_type');
            $table->index('duration_type');
            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
        });
    }
};

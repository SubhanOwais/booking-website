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
        Schema::create('special_bookings', function (Blueprint $table) {
            $table->id();
            // Passenger Info
            $table->string('passenger_name');
            $table->string('passenger_phone');
            $table->string('passenger_email')->nullable();

            // Trip Info
            $table->unsignedBigInteger('from_city_id');
            $table->unsignedBigInteger('to_city_id');
            $table->date('travel_date');
            $table->time('preferred_time')->nullable();

            // Bus Info
            $table->unsignedBigInteger('company_id')->nullable();
            $table->enum('bus_type', ['standard', 'luxury', 'sleeper', 'mini_coach', 'double_decker'])->default('standard');

            // Extra
            $table->text('special_notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('change_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('from_city_id')->references('id')->on('cities')->onDelete('restrict');
            $table->foreign('to_city_id')->references('id')->on('cities')->onDelete('restrict'); // FIXED
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['travel_date', 'status']);
            $table->index('from_city_id');
            $table->index('to_city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_bookings');
    }
};

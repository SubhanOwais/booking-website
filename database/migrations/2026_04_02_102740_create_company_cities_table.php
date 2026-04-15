<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_cities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('city_id');          // company's own internal ID (no FK)
            $table->unsignedBigInteger('key_id');           // global cities.id (FK)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // 🔥 CORRECT FOREIGN KEY: key_id references cities.id
            $table->foreign('key_id')->references('id')->on('cities')->onDelete('restrict');

            // Prevent duplicate global city per company
            $table->unique(['company_id', 'key_id']);

            // Prevent duplicate internal ID per company
            $table->unique(['company_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_cities');
    }
};

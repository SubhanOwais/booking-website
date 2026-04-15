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
        Schema::create('city_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('City_Id');
            $table->json('City_Mapping');
            $table->boolean('Active')->default(true);
            $table->timestamps();

            $table->foreign('City_Id')
                ->references('id')
                ->on('cities')
                ->onDelete('cascade');

            $table->index('City_Id');
            $table->index('Active');
            $table->index(['Active', 'City_Id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_mappings');
    }
};

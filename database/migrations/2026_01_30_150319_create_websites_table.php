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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('name'); // Website name
            $table->string('logo')->nullable(); // Logo path
            $table->text('description')->nullable(); // Description
            $table->string('helpline_number')->nullable(); // Contact number
            $table->string('email')->nullable(); // Contact email
            $table->string('address')->nullable(); // Physical address
            $table->string('favicon')->nullable(); // Small website icon

            // Social Media Links
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('youtube')->nullable();
            $table->string('tiktok')->nullable();

            // SEO / Metadata
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};

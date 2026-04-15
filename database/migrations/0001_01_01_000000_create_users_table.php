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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('Full_Name', 100)->nullable();
            $table->string('User_Name', 100)->unique();
            $table->string('Email', 100)->nullable()->unique();
            $table->string('Phone_Number', 20)->nullable()->unique();
            $table->string('Emergency_Number', 20)->nullable();
            $table->string('Address')->nullable();
            $table->string('CNIC')->nullable();
            $table->string('Profile_Picture')->nullable();
            $table->string('Password');

            // Roles & Status
            $table->boolean('IsSuperAdmin')->default(false);
            $table->boolean('IsAdmin')->default(false);
            $table->boolean('Is_Active')->default(true);
            $table->string('User_Type', 50)->default('WebCustomer');

            // Email Verification
            $table->string('email_verification_token')->nullable();
            $table->boolean('is_email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();

            // Meta
            $table->timestamp('LastLogin')->nullable();
            $table->unsignedBigInteger('Created_By')->nullable();
            $table->unsignedBigInteger('Changed_By')->nullable();
            $table->datetime('Created_On')->nullable();
            $table->datetime('Changed_On')->nullable();
            $table->longText('Permissions')->nullable();
            $table->string('Company_Id')->nullable();

            // Add indexes
            $table->index('User_Name');
            $table->index('Email');
            $table->index('Phone_Number');
            $table->index('User_Type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

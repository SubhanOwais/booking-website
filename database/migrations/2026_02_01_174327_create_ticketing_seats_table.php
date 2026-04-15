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
        Schema::create('ticketing_seats', function (Blueprint $table) {
            $table->id();

            // ===== Ticketing / Seat Information =====
            $table->string('Ticketing_Schedule_ID')->nullable();
            $table->string('Seat_Id')->nullable(); // Single seat ID per record
            $table->string('Seat_No')->nullable(); // Single seat number per record
            $table->string('Status')->default('Booked'); // Pending, Booked, Cancelled, Completed
            $table->string('Source_ID')->nullable();
            $table->string('Destination_ID')->nullable();
            $table->decimal('Fare', 10, 2)->default(0); // Fare for this specific seat
            $table->decimal('Original_Fare', 10, 2)->default(0); // Original fare before discount
            $table->decimal('Discount', 10, 2)->default(0); // Discount applied to this seat

            $table->date('Travel_Date')->nullable();
            $table->time('Travel_Time')->nullable();

            // ===== Passenger Information =====
            $table->unsignedBigInteger('Customer_Id')->nullable();
            $table->string('Passenger_Name')->nullable();
            $table->string('Contact_No')->nullable();
            $table->string('Emergency_Contact', 20)->nullable();
            $table->string('CNIC', 13)->nullable();
            $table->enum('Gender', ['Male', 'Female'])->nullable();
            $table->string('Company_Name')->nullable();
            $table->string('Company_Id')->nullable();

            // ===== PNR & Issue Information =====
            $table->string('PNR_No', 50)->unique()->nullable(); // Unique PNR per seat
            $table->dateTime('Issue_Date')->nullable();
            $table->string('Boarding_Terminal_Id')->nullable();
            $table->string('Boarding_Terminal')->nullable();
            $table->string('Issued_By')->nullable();
            $table->boolean('Is_SMS_Sent')->default(false);
            $table->boolean('Telenor')->default(false);
            $table->boolean('IsMissed')->default(false);
            $table->string('CollectionPoint')->nullable();
            $table->string('Invoice')->nullable();
            $table->string('Bus_Service')->nullable();

            // ===== Payment & Refund Information =====
            $table->dateTime('PaymentDate')->nullable();
            $table->dateTime('Refund_Date')->nullable();
            $table->longText('Refund_Reason')->nullable();
            $table->string('Refund_Amount')->nullable();
            $table->string('Refund_By')->nullable();
            $table->boolean('Is_Return')->default(false);
            $table->integer('Points')->default(0);

            // ===== Timestamps =====
            $table->timestamps();

            // ===== Indexes =====
            $table->index('PNR_No');
            $table->index('Ticketing_Schedule_ID');
            $table->index('Seat_Id');
            $table->index('CNIC');
            $table->index('Customer_Id');
            $table->index('Status');
            $table->index(['Customer_Id', 'Ticketing_Schedule_ID', 'Status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticketing_seats');
    }
};

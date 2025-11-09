<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('patient_name');
            $table->string('doctor_name')->nullable();
            $table->string('service_description');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // cash, credit_card, debit_card, gcash, paymaya
            $table->timestamp('payment_date');
            $table->string('processed_by'); // Staff member who processed the payment
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('clinic_id')->references('id')->on('clinic_infos')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_receipts');
    }
}

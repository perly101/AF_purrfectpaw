<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status'); // unpaid, paid, refunded
            $table->decimal('amount', 10, 2)->nullable()->after('payment_status');
            $table->string('payment_method')->nullable()->after('amount'); // cash, credit_card, debit_card, gcash, paymaya
            $table->string('receipt_number')->nullable()->unique()->after('payment_method');
            $table->timestamp('payment_date')->nullable()->after('receipt_number');
            $table->text('payment_notes')->nullable()->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'amount',
                'payment_method',
                'receipt_number',
                'payment_date',
                'payment_notes'
            ]);
        });
    }
}

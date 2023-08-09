<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgencyReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_receipts', function (Blueprint $table) {
            $table->id();
            $table->mediumText('ReceiptNo')->nullable();
            $table->integer('AgencyID')->nullable();
            $table->smallInteger('IsAnnualBill')->nullable();
            $table->integer('BillID')->nullable();
            $table->smallInteger('IsNewHoarding')->nullable();
            $table->integer('NewHoardingID')->nullable();
            $table->smallInteger('IsAgencyRenewal')->nullable();
            $table->decimal('Amount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('NetAmount', $precision = 18, $scale = 2)->nullable();
            $table->integer('OnlinePaymentID')->nullable();
            $table->mediumText('PmtMode')->nullable();
            $table->mediumText('Bank')->nullable();
            $table->mediumText('MRNo')->nullable();
            $table->mediumText('DraftNo')->nullable();
            $table->dateTime('PaymentDate')->nullable();
            $table->dateTime('DraftDate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agency_receipts');
    }
}

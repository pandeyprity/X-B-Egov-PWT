<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->mediumText('ReceiptNo')->nullable();
            $table->dateTime('PaymentDate')->nullable();
            $table->string('RenewalID', 15)->nullable();
            $table->mediumText('DraftNo')->nullable();
            $table->dateTime('DraftDate')->nullable();
            $table->mediumText('BankName')->nullable();
            $table->mediumText('MRNo')->nullable();
            $table->integer('PaymentAmount')->nullable();
            $table->integer('Amount')->nullable();
            $table->integer('Fine')->nullable();
            $table->integer('Interest')->nullable();
            $table->integer('ReceiptHeadID')->nullable();
            $table->mediumText('ReceivedFrom')->nullable();
            $table->mediumText('RecdAddress')->nullable();
            $table->mediumText('RecdTelephone')->nullable();
            $table->mediumText('Remarks')->nullable();
            $table->integer('CreatedBy')->nullable();
            $table->smallInteger('AgencyRenewal')->nullable();
            $table->smallInteger('HoardingRenewal')->nullable();
            $table->smallInteger('VehicleRenewal')->nullable();
            $table->smallInteger('PrivateLandRenewal')->nullable();
            $table->smallInteger('BanquetRenewal')->nullable();
            $table->smallInteger('LodgeRenewal')->nullable();
            $table->smallInteger('DharmshalaRenewal')->nullable();
            $table->smallInteger('ShopRental')->nullable();
            $table->smallInteger('Other')->nullable();
            $table->string('CreatedOn', 29)->nullable();
            $table->integer('ModifiedBy')->nullable();
            $table->string('ModifiedOn', 29)->nullable();
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
        Schema::dropIfExists('payments');
    }
}

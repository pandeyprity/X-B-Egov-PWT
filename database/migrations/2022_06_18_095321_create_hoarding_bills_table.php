<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHoardingBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hoarding_bills', function (Blueprint $table) {
            $table->id();
            $table->integer('BillID')->nullable();
            $table->integer('HoardingID')->nullable();
            $table->date('BillDate')->nullable();
            $table->mediumText('RenewalYear')->nullable();
            $table->decimal('Arrear', $precision = 18, $scale = 2)->nullable();
            $table->decimal('Interest', $precision = 18, $scale = 2)->nullable();
            $table->decimal('LicenseFee', $precision = 18, $scale = 2)->nullable();
            $table->decimal('AnnualRate', $precision = 18, $scale = 2)->nullable();
            $table->decimal('DailyRate', $precision = 18, $scale = 2)->nullable();
            $table->integer('CalcDays')->nullable();
            $table->integer('BoardArea')->nullable();
            $table->decimal('Demand', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('NetAmount', $precision = 18, $scale = 2)->nullable();
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
        Schema::dropIfExists('hoarding_bills');
    }
}

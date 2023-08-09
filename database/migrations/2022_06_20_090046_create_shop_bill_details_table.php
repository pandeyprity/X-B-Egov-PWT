<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopBillDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_bill_details', function (Blueprint $table) {
            $table->id();
            $table->integer('BillID')->nullable();
            $table->integer('ShopID')->nullable();
            $table->mediumText('LetterNo')->nullable();
            $table->mediumText('ArrearPeriod')->nullable();
            $table->decimal('Arrear', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearGSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearGST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearInterestPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('Interest', $precision = 18, $scale = 2)->nullable();
            $table->mediumText('BillTerms')->nullable();
            $table->integer('Area')->nullable();
            $table->decimal('Rate', $precision = 18, $scale = 2)->nullable();
            $table->integer('Units')->nullable();
            $table->decimal('Demand', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('TotalDemand', $precision = 18, $scale = 2)->nullable();
            $table->mediumText('IPAddress')->nullable();
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
        Schema::dropIfExists('shop_bill_details');
    }
}

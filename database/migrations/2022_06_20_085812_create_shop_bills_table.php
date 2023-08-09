<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_bills', function (Blueprint $table) {
            $table->id();
            $table->dateTime('BillDate')->nullable();
            $table->mediumText('BillPeriod')->nullable();
            $table->mediumText('LetterNo')->nullable();
            $table->decimal('GSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('InterestPercent', $precision = 18, $scale = 2)->nullable();
            $table->mediumText('Remarks')->nullable();
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
        Schema::dropIfExists('shop_bills');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_payments', function (Blueprint $table) {
            $table->id();

            $table->integer('ShopID')->nullable();
            $table->integer('BillDetailID')->nullable();
            $table->dateTime('PaymentDate')->nullable();
            $table->decimal('PaymentAmount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('CalculatedAmount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('CalculatedTax', $precision = 18, $scale = 2)->nullable();
            $table->mediumText('PaymentMethod')->nullable();
            $table->mediumText('UserName')->nullable();
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
        Schema::dropIfExists('shop_payments');
    }
}

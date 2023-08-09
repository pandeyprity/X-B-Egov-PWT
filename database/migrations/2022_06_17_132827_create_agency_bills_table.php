<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgencyBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_bills', function (Blueprint $table) {
            $table->id();
            $table->dateTime('BillDate')->nullable();
            $table->mediumText('BillYear')->nullable();
            $table->mediumText('BillNo')->nullable();
            $table->integer('AgencyID')->nullable();
            $table->decimal('Amount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('NetAmount', $precision = 18, $scale = 2)->nullable();
            $table->dateTime('DueDate')->nullable();
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
        Schema::dropIfExists('agency_bills');
    }
}

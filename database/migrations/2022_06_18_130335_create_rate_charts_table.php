<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRateChartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_charts', function (Blueprint $table) {
            $table->string('ID', 10)->nullable();
            $table->string('HType', 10)->nullable();
            $table->string('Zone', 1)->nullable();
            $table->integer('LicenseFee')->nullable();
            $table->decimal('AnnualRate', $precision = 18, $scale = 2)->nullable();
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
        Schema::dropIfExists('rate_charts');
    }
}

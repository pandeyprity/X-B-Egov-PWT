<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHoardingRateChartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hoarding_rate_charts', function (Blueprint $table) {
            $table->id();
            $table->mediumText('LicenseYear')->nullable();
            $table->mediumText('Zone')->nullable();
            $table->decimal('LicenseFee', $precision = 18, $scale = 2)->nullable();
            $table->decimal('Rate', $precision = 18, $scale = 2)->nullable();
            $table->decimal('DailyRate', $precision = 18, $scale = 3)->nullable();
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
        Schema::dropIfExists('hoarding_rate_charts');
    }
}

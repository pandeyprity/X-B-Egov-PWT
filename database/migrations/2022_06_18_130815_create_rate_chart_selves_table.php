<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRateChartSelvesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_chart_selves', function (Blueprint $table) {
            $table->integer('ID')->nullable();
            $table->string('Zone', 1)->nullable();
            $table->integer('StartRange')->nullable();
            $table->integer('EndRange')->nullable();
            $table->integer('Rate')->nullable();
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
        Schema::dropIfExists('rate_chart_selves');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDharmshalaDisposalDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dharmshala_disposal_days', function (Blueprint $table) {
            $table->id();
            $table->string('RenewalID', 15)->nullable();
            $table->string('CreatedOn', 29)->nullable();
            $table->dateTime('LastTranDate')->nullable();
            $table->integer('DisposalDays')->nullable();
            $table->mediumText('ApplicationStatus')->nullable();
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
        Schema::dropIfExists('dharmshala_disposal_days');
    }
}

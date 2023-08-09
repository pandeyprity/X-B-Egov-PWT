<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParamStringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('param_strings', function (Blueprint $table) {
            $table->integer('ID')->nullable();
            $table->integer('ParamCategoryID')->nullable();
            $table->mediumText('StringParameter')->nullable();
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
        Schema::dropIfExists('param_strings');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradeParamCategoryTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trade_param_category_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('category_type')->nullable();
            $table->bigInteger('ulb_id')->nullable();
            $table->smallInteger('status')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trade_param_category_types', function (Blueprint $table) {
            //
        });
    }
}

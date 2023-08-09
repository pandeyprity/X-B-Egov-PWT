<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveSafFloorDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_saf_floor_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('saf_dtl_id');
            $table->bigInteger('floor_mstr_id')->nullable();
            $table->bigInteger('usage_type_mstr_id')->nullable();
            $table->bigInteger('const_type_mstr_id')->nullable();
            $table->bigInteger('occupancy_type_mstr_id')->nullable();
            $table->decimal('builtup_area', 18)->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_upto')->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->decimal('carpet_area', 18)->nullable();
            $table->bigInteger('prop_floor_details_id')->nullable();
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
        Schema::dropIfExists('active_saf_floor_details');
    }
}

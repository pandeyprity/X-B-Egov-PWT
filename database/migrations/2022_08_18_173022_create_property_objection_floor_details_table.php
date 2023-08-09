<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyObjectionFloorDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_objection_floor_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('prop_dtl_id')->nullable();
            $table->bigInteger('objection_id')->nullable();
            $table->bigInteger('objection_type_id')->nullable();
            $table->bigInteger('prop_floor_dtl_id')->nullable();
            $table->bigInteger('floor_mstr_id')->nullable();
            $table->integer('usage_type_mstr_id')->nullable();
            $table->bigInteger('occupancy_type_mstr_id')->nullable();
            $table->bigInteger('const_type_mstr_id')->nullable();
            $table->decimal('builtup_area', 20)->nullable();
            $table->decimal('carpet_area', 20)->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_upto')->nullable();
            $table->text('remarks')->nullable();
            $table->string('objection_by', 200)->nullable();
            $table->smallInteger('is_removed')->nullable()->default(0);
            $table->smallInteger('status')->nullable()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->softDeletes();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_objection_floor_details');
    }
}

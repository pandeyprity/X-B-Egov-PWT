<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropOwnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_owners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('property_id');
            $table->string('owner_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('relation_type')->nullable();
            $table->bigInteger('mobile_no')->nullable();
            $table->string('email')->nullable();
            $table->string('pan_no')->nullable();
            $table->bigInteger('aadhar_no')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('dob')->nullable();
            $table->boolean('is_armed_force')->nullable()->default(false);
            $table->boolean('is_specially_abled')->nullable()->default(false);
            $table->bigInteger('emp_details_id')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->bigInteger('rmc_prop_owner_dtl_id')->nullable();
            $table->bigInteger('rmc_prop_dtl_id')->nullable();
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
        Schema::dropIfExists('prop_owners');
    }
}

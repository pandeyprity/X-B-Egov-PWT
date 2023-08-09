<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyObjectionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_objection_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('objection_id')->nullable();
            $table->bigInteger('objection_type_id')->nullable();
            $table->string('according_assessment', 500)->nullable();
            $table->decimal('assess_area', 20)->nullable();
            $table->date('assess_date')->nullable();
            $table->string('according_applicant', 500)->nullable();
            $table->decimal('applicant_area', 20)->nullable();
            $table->date('applicant_date')->nullable();
            $table->string('objection_by', 200)->nullable();
            $table->bigInteger('user_id')->nullable();
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
        Schema::dropIfExists('property_objection_details');
    }
}

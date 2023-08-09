<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropDeamndsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_deamnds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('property_id');
            $table->integer('qtr')->nullable();
            $table->decimal('holding_tax', 18)->nullable();
            $table->decimal('water_tax', 18)->nullable();
            $table->decimal('education_cess', 18)->nullable();
            $table->decimal('health_tax', 18)->nullable();
            $table->decimal('latrine_tax', 18)->nullable();
            $table->decimal('additional_tax', 18)->nullable();
            $table->text('collection_type')->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('update_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prop_deamnds');
    }
}

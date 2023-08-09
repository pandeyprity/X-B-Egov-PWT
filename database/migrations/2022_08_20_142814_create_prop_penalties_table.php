<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropPenaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_penalties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('tran_id')->nullable();
            $table->bigInteger('property_id');
            $table->bigInteger('penalty_type_id');
            $table->date('penalty_date')->nullable()->useCurrent();
            $table->decimal('amount', 18)->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->text('description')->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
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
        Schema::dropIfExists('prop_penalties');
    }
}

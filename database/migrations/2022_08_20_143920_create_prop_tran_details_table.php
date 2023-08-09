<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropTranDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_tran_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('tran_id');
            $table->bigInteger('demand_id');
            $table->decimal('total_demand', 18)->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->timestamp('create_at')->nullable()->useCurrent();
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
        Schema::dropIfExists('prop_tran_details');
    }
}

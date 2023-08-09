<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropRebatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_rebates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('property_id')->nullable();
            $table->bigInteger('rebate_type_id')->nullable();
            $table->date('tran_date')->nullable();
            $table->decimal('amount', 18)->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->text('description')->nullable();
            $table->timestamp('create_at')->nullable()->useCurrent();
            $table->softDeletes();
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
        Schema::dropIfExists('prop_rebates');
    }
}

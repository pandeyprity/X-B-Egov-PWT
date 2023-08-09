<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropTransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_trans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('property_id')->nullable();
            $table->bigInteger('saf_id')->nullable();
            $table->date('tran_date')->nullable();
            $table->text('tran_no')->nullable();
            $table->string('payment_mode', 20)->nullable();
            $table->decimal('amount', 18)->nullable();
            $table->decimal('penalty', 18)->nullable();
            $table->decimal('rebate', 18)->nullable();
            $table->decimal('total_amount', 18)->nullable();
            $table->smallInteger('status')->nullable();
            $table->smallInteger('verification_status')->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->date('verification_date')->nullable();
            $table->bigInteger('verification_by')->nullable();
            $table->bigInteger('ulb_id')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
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
        Schema::dropIfExists('prop_trans');
    }
}

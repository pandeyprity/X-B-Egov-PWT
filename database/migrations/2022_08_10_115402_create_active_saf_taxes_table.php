<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveSafTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_saf_taxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('saf_dtl_id')->nullable();
            $table->bigInteger('fy_mstr_id')->nullable();
            $table->decimal('arv', 10, 0)->nullable()->default(0);
            $table->decimal('holding_tax', 10, 0)->nullable()->default(0);
            $table->decimal('water_tax', 10, 0)->nullable()->default(0);
            $table->decimal('education_cess', 10, 0)->nullable()->default(0);
            $table->decimal('health_cess', 10, 0)->nullable()->default(0);
            $table->decimal('latrine_tax', 10, 0)->nullable()->default(0);
            $table->decimal('additional_tax', 10, 0)->nullable()->default(0);
            $table->timestamp('created_on')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->integer('qtr')->nullable();
            $table->bigInteger('rmc_saf_tax_dtl_id')->nullable();
            $table->bigInteger('rmc_saf_dtl_id')->nullable();
            $table->string('fyear', 9)->nullable();
            $table->decimal('quarterly_tax', 18)->nullable();
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
        Schema::dropIfExists('active_saf_taxes');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('params', function (Blueprint $table) {
            $table->id();
            $table->integer('self_ad_counter')->nullable();
            $table->mediumText('self_ad_prefix')->nullable();

            $table->integer('vehicle_counter')->nullable();
            $table->mediumText('vehicle_prefix')->nullable();

            $table->integer('dharmasala_counter')->nullable();
            $table->mediumText('dharmasala_prefix')->nullable();

            $table->integer('land_counter')->nullable();
            $table->mediumText('land_prefix')->nullable();

            $table->integer('banquet_counter')->nullable();
            $table->mediumText('banquet_prefix')->nullable();

            $table->integer('lodge_counter')->nullable();
            $table->mediumText('lodge_prefix')->nullable();

            $table->integer('agency_counter')->nullable();
            $table->mediumText('agency_prefix')->nullable();

            $table->integer('hoarding_counter')->nullable();
            $table->mediumText('hoarding_prefix')->nullable();
            $table->smallInteger('allow_registration')->nullable();
            $table->integer('vendor_counter')->nullable();
            $table->mediumText('vendor_prefix')->nullable();
            $table->mediumText('survey_prefix')->nullable();
            $table->integer('survey_counter')->nullable();
            $table->integer('last_bill_year')->nullable();
            $table->integer('shop_penalty')->nullable();
            $table->integer('hoarding_workflow_id')->nullable();
            $table->integer('lodge_workflow_id')->nullable();
            $table->integer('banquet_workflow_id')->nullable();
            $table->integer('agency_license_fee')->nullable();
            $table->decimal('vehicle_annual_rate', $precision = 18, $scale = 2)->nullable();
            $table->integer('vehicle_license_fee')->nullable();
            $table->integer('gst_rate')->nullable();
            $table->mediumText('lodge_sign_path')->nullable();
            $table->mediumText('banquet_sign_path')->nullable();
            $table->mediumText('agency_sign_path')->nullable();
            $table->mediumText('shop_sign_path')->nullable();
            $table->mediumText('self_sign_path')->nullable();
            $table->mediumText('vehicle_sign_path')->nullable();
            $table->mediumText('pl_sign_path')->nullable();
            $table->mediumText('hoarding_sign_path')->nullable();
            $table->mediumText('dharmsala_sign_path')->nullable();
            $table->mediumText('current_finance_year')->nullable();
            $table->mediumText('allow_create_user')->nullable();
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
        Schema::dropIfExists('params');
    }
}

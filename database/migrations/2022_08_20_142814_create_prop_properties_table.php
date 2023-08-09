<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prop_properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('saf_id');
            $table->string('assessment_type', 100)->nullable();
            $table->string('holding_type', 25)->nullable();
            $table->text('holding_no')->nullable();
            $table->text('new_holding_no')->nullable();
            $table->bigInteger('ward_mstr_id')->nullable();
            $table->bigInteger('ulb_id')->nullable();
            $table->bigInteger('zone_mstr_id')->nullable();
            $table->bigInteger('new_ward_mstr_id')->nullable();
            $table->bigInteger('ownership_type_mstr_id')->nullable();
            $table->bigInteger('prop_type_mstr_id')->nullable();
            $table->text('appartment_name')->nullable();
            $table->boolean('no_electric_connection')->nullable();
            $table->text('elect_consumer_no')->nullable();
            $table->text('elect_acc_no')->nullable();
            $table->text('elect_bind_book_no')->nullable();
            $table->text('elect_cons_category')->nullable();
            $table->text('building_plan_approval_no')->nullable();
            $table->date('building_plan_approval_date')->nullable();
            $table->text('water_conn_no')->nullable();
            $table->date('water_conn_date')->nullable();
            $table->text('khata_no')->nullable();
            $table->text('plot_no')->nullable();
            $table->text('village_mauja_name')->nullable();
            $table->bigInteger('road_type_mstr_id')->nullable();
            $table->decimal('area_of_plot', 18)->nullable();
            $table->text('prop_address')->nullable();
            $table->text('prop_city')->nullable();
            $table->text('prop_dist')->nullable();
            $table->text('prop_pin_code')->nullable();
            $table->text('corr_address')->nullable();
            $table->text('corr_city')->nullable();
            $table->text('corr_dist')->nullable();
            $table->text('corr_pin_code')->nullable();
            $table->boolean('is_mobile_tower')->nullable();
            $table->decimal('tower_area', 18)->nullable();
            $table->date('tower_installation_date')->nullable();
            $table->boolean('is_hoarding_board')->nullable();
            $table->decimal('hoarding_area', 18)->nullable();
            $table->date('hoarding_installation_date')->nullable();
            $table->boolean('is_petrol_pump')->nullable();
            $table->decimal('under_ground_area', 18)->nullable();
            $table->date('petrol_pump_completion_date')->nullable();
            $table->boolean('is_water_harvesting')->nullable();
            $table->date('occupation_date')->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->integer('for_sub_holding')->nullable()->default(0);
            $table->integer('saf_hold_status')->nullable()->default(0);
            $table->text('entry_type')->nullable();
            $table->text('prop_state')->nullable();
            $table->text('corr_state')->nullable();
            $table->date('flat_registry_date')->nullable();
            $table->integer('is_old')->nullable()->default(0);
            $table->bigInteger('govt_saf_dtl_id')->nullable();
            $table->bigInteger('apartment_details_id')->nullable();
            $table->timestamp('application_date')->nullable();
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
        Schema::dropIfExists('prop_properties');
    }
}

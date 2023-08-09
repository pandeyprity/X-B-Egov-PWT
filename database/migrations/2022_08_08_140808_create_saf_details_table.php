<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSafDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saf_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('has_previous_holding_no')->nullable();
            $table->string('previous_holding_id')->nullable();
            $table->bigInteger('previous_ward_mstr_id')->nullable();
            $table->boolean('is_owner_changed')->nullable();
            $table->bigInteger('transfer_mode_mstr_id')->nullable();
            $table->string('saf_no')->nullable();
            $table->string('holding_no')->nullable();
            $table->bigInteger('ward_mstr_id')->nullable();
            $table->bigInteger('ownership_type_mstr_id')->nullable();
            $table->bigInteger('prop_type_mstr_id')->nullable();
            $table->string('appartment_name')->nullable();
            $table->date('flat_registry_date')->nullable();
            $table->bigInteger('zone_mstr_id')->nullable();
            $table->boolean('no_electric_connection')->nullable();
            $table->string('elect_consumer_no')->nullable();
            $table->string('elect_acc_no')->nullable();
            $table->string('elect_bind_book_no')->nullable();
            $table->string('elect_cons_category')->nullable();
            $table->string('building_plan_approval_no')->nullable();
            $table->date('building_plan_approval_date')->nullable();
            $table->string('water_conn_no')->nullable();
            $table->date('water_conn_date')->nullable();
            $table->string('khata_no')->nullable();
            $table->string('plot_no')->nullable();
            $table->string('village_mauja_name')->nullable();
            $table->bigInteger('road_type_mstr_id')->nullable();
            $table->decimal('area_of_plot', 18)->nullable();
            $table->string('prop_address')->nullable();
            $table->string('prop_city')->nullable();
            $table->string('prop_dist', 100)->nullable()->default('NULL::character varying');
            $table->string('prop_pin_code', 6)->nullable()->default('NULL::character varying');
            $table->boolean('is_corr_add_differ')->nullable();
            $table->string('corr_address')->nullable();
            $table->string('corr_city')->nullable();
            $table->string('corr_dist', 100)->nullable()->default('NULL::character varying');
            $table->string('corr_pin_code', 6)->nullable()->default('NULL::character varying');
            $table->boolean('is_mobile_tower')->nullable();
            $table->decimal('tower_area', 18)->nullable();
            $table->date('tower_installation_date')->nullable();
            $table->boolean('is_hoarding_board')->nullable();
            $table->decimal('hoarding_area', 18)->nullable();
            $table->date('hoarding_installation_date')->nullable();
            $table->boolean('is_petrol_pump')->nullable()->default(false);
            $table->decimal('under_ground_area', 18)->nullable();
            $table->date('petrol_pump_completion_date')->nullable();
            $table->boolean('is_water_harvesting')->nullable()->default(false);
            $table->date('land_occupation_date')->nullable();
            $table->integer('payment_status')->nullable()->default(0);
            $table->integer('doc_verify_status')->nullable()->default(0);
            $table->date('doc_verify_date')->nullable();
            $table->bigInteger('doc_verify_emp_details_id')->nullable();
            $table->string('doc_verify_cancel_remarks')->nullable();
            $table->integer('field_verify_status')->nullable()->default(0);
            $table->date('field_verify_date')->nullable();
            $table->bigInteger('field_verify_emp_details_id')->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->timestamp('updated_on')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->date('apply_date')->nullable();
            $table->integer('saf_pending_status')->nullable()->default(0);
            $table->string('assessment_type', 100)->nullable()->default('NULL::character varying');
            $table->integer('doc_upload_status')->nullable()->default(0);
            $table->bigInteger('saf_distributed_dtl_id')->nullable();
            $table->bigInteger('prop_dtl_id')->nullable()->default(0);
            $table->string('prop_state', 50)->nullable();
            $table->string('corr_state', 50)->nullable();
            $table->string('holding_type', 100)->nullable()->default('NULL::character varying');
            $table->string('ip_address', 70)->nullable();
            $table->bigInteger('property_assessment_id')->nullable();
            $table->bigInteger('new_ward_mstr_id')->nullable();
            $table->smallInteger('percentage_of_property_transfer')->nullable();
            $table->bigInteger('apartment_details_id')->nullable();
            $table->integer('current_user')->nullable();
            $table->integer('initiator_id')->nullable();
            $table->integer('finisher_id')->nullable();
            $table->integer('workflow_id')->nullable();
            $table->integer('ulb_id')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('saf_details');
    }
}

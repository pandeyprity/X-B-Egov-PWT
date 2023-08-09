<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TempSelfAdvertisement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_self_advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 15)->nullable();
            $table->integer('ulb_id')->nullable();
            $table->mediumText('license_year')->nullable();
            $table->mediumText('applicant')->nullable();
            $table->mediumText('father')->nullable();
            $table->mediumText('email')->nullable();
            $table->mediumText('residence_address')->nullable();
            $table->mediumText('ward_no')->nullable();
            $table->mediumText('permanent_address')->nullable();
            $table->mediumText('entity_name')->nullable();
            $table->mediumText('entity_address')->nullable();
            $table->mediumText('entity_ward')->nullable();
            $table->string('mobile_no', 10)->nullable();
            $table->mediumText('aadhar_no')->nullable();
            $table->mediumText('trade_license_no')->nullable();
            $table->mediumText('holding_no')->nullable();
            $table->mediumText('gst_no')->nullable();
            $table->mediumText('longitude')->nullable();
            $table->mediumText('latitude')->nullable();
            $table->mediumText('display_area')->nullable();
            $table->mediumText('display_type')->nullable();
            $table->mediumText('installation_location')->nullable();
            $table->mediumText('brand_display_name')->nullable();
            $table->mediumText('aadhar_path')->nullable();
            $table->mediumText('trade_license_path')->nullable();
            $table->mediumText('holding_no_path')->nullable();
            $table->mediumText('gps_photo_path')->nullable();
            $table->mediumText('gst_path')->nullable();
            $table->string('zone', 10)->nullable();
            $table->mediumText('proceeding1_photo')->nullable();
            $table->mediumText('proceeding2_photo')->nullable();
            $table->mediumText('proceeding3_photo')->nullable();
            $table->mediumText('extra_doc1')->nullable();
            $table->mediumText('extra_doc2')->nullable();
            $table->integer('workflow_id')->nullable();
            $table->mediumText('current_user')->nullable();
            $table->boolean('is_workflow_pending')->nullable();
            $table->boolean('is_action_pending')->nullable();
            $table->mediumText('initiator')->nullable();
            $table->mediumText('approver')->nullable();
            $table->integer('inspector_id')->nullable();
            $table->mediumText('inspection_remarks')->nullable();
            $table->smallInteger('pending')->nullable();
            $table->smallInteger('approved')->nullable();
            $table->smallInteger('rejected')->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->smallInteger('paid')->nullable();
            $table->mediumText('rejection_reason')->nullable();
            $table->mediumText('application_status')->nullable();

            $table->decimal('license_fee', $precision = 18, $scale = 2)->nullable();
            $table->decimal('amount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('gst', $precision = 18, $scale = 2)->nullable();
            $table->decimal('net_amount', $precision = 18, $scale = 2)->nullable();

            $table->integer('online_payment_id')->nullable();
            $table->mediumText('pmt_mode')->nullable();
            $table->mediumText('bank')->nullable();
            $table->mediumText('mr_no')->nullable();
            $table->mediumText('draft_no')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('draft_date')->nullable();
            $table->integer('modified_by')->nullable();
            $table->mediumText('signature_path')->nullable();
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
        Schema::dropIfExists('temp_self_advertisements');
    }
}

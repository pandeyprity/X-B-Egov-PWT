<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('renewal_id', 15)->nullable();
            $table->string('unique_id', 15)->nullable();
            $table->smallInteger('renewal')->nullable();
            $table->date('application_date')->nullable();
            $table->mediumText('registration_no')->nullable();
            $table->mediumText('entity_name')->nullable();
            $table->mediumText('entity_type')->nullable();
            $table->mediumText('address')->nullable();
            $table->mediumText('mobile_no')->nullable();
            $table->mediumText('telephone')->nullable();
            $table->mediumText('fax')->nullable();
            $table->mediumText('email')->nullable();
            $table->mediumText('director1_name')->nullable();
            $table->mediumText('director2_name')->nullable();
            $table->mediumText('director3-name')->nullable();
            $table->mediumText('director4_name')->nullable();
            $table->mediumText('director1_mobile')->nullable();
            $table->mediumText('director2_mobile')->nullable();
            $table->mediumText('director3_mobile')->nullable();
            $table->mediumText('director4_mobile')->nullable();
            $table->mediumText('director1_email')->nullable();
            $table->mediumText('director2_email')->nullable();
            $table->mediumText('director3_email')->nullable();
            $table->mediumText('director4_email')->nullable();
            $table->mediumText('pan_no')->nullable();
            $table->mediumText('gst_no')->nullable();
            $table->smallInteger('blacklisted')->nullable();
            $table->decimal('pending_amount', $precision = 18, $scale = 2)->nullable();
            $table->smallInteger('pending_court_case')->nullable();
            $table->mediumText('proceeding1_path')->nullable();
            $table->mediumText('proceeding2_path')->nullable();
            $table->mediumText('proceeding3_path')->nullable();
            $table->mediumText('extra_doc1')->nullable();
            $table->mediumText('extra_doc2')->nullable();
            $table->mediumText('gst_path')->nullable();
            $table->mediumText('it_return_path1')->nullable();
            $table->mediumText('it_return_path2')->nullable();
            $table->mediumText('office_address_path')->nullable();
            $table->mediumText('pan_no_path')->nullable();
            $table->mediumText('director1_aadhar_path')->nullable();
            $table->mediumText('director2_aadhar_path')->nullable();
            $table->mediumText('director3_aadhar_path')->nullable();
            $table->mediumText('director4_aadhar_path')->nullable();
            $table->mediumText('affidavit_path')->nullable();
            $table->integer('workflow_id')->nullable();
            $table->mediumText('current_user')->nullable();
            $table->mediumText('initiator')->nullable();
            $table->mediumText('approver')->nullable();
            $table->smallInteger('pending')->nullable();
            $table->smallInteger('approved')->nullable();
            $table->smallInteger('rejected')->nullable();
            $table->date('approval_date')->nullable();
            $table->smallInteger('paid')->nullable();
            $table->mediumText('rejection_reason')->nullable();
            $table->mediumText('application_status')->nullable();
            $table->string('license_from')->nullable();
            $table->smallInteger('active')->nullable();
            $table->decimal('license_fee', $precision = 18, $scale = 2)->nullable();
            $table->decimal('amount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('gst', $precision = 18, $scale = 2)->nullable();
            $table->decimal('net-amount', $precision = 18, $scale = 2)->nullable();
            $table->integer('online-payment_id')->nullable();
            $table->mediumText('pmt-mode')->nullable();
            $table->mediumText('bank')->nullable();
            $table->mediumText('mr_no')->nullable();
            $table->mediumText('draft_no')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('draft_date')->nullable();
            $table->mediumText('created_on')->nullable();
            $table->integer('modified_by')->nullable();
            $table->string('modified_on', 29)->nullable();
            $table->mediumText('signature_path')->nullable();
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
        Schema::dropIfExists('agencies');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyObjectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_objections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('prop_dtl_id');
            $table->bigInteger('saf_dtl_id');
            $table->string('objection_no', 20)->nullable();
            $table->string('holding_no', 20)->nullable();
            $table->bigInteger('ward_id')->nullable();
            $table->text('objection_form_doc')->nullable();
            $table->text('evidence_document')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('current_user')->nullable();
            $table->bigInteger('initiator_id')->nullable();
            $table->bigInteger('finisher_id')->nullable();
            $table->bigInteger('workflow_id')->nullable();
            $table->smallInteger('is_escalate')->nullable();
            $table->bigInteger('escalate_by')->nullable();
            $table->smallInteger('status')->nullable()->default(1);
            $table->smallInteger('level_status')->nullable()->default(1)->comment('0 Rejected, 1 Pending at IT Head, 2 Tax Collector, 3 Section Head, 4 Executive Officer, 5 Approved');
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
        Schema::dropIfExists('property_objections');
    }
}

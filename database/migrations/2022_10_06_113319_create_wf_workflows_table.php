<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfWorkflowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wf_workflows', function (Blueprint $table) {
            $table->id();
            $table->integer('wf_master_id');
            $table->integer('ulb_id');
            $table->string('alt_name');
            $table->boolean('is_doc_required');
            $table->boolean('is_suspended');
            $table->integer('user_id');
            $table->smallInteger('status')->nullable()->default(1);
            $table->dateTime('stamp_date_time')->nullable();
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
        Schema::dropIfExists('wf_workflows');
    }
}

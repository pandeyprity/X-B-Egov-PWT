<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUlbWorkflowMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ulb_workflow_masters', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ulb_id')->nullable();
            $table->bigInteger('module_id')->nullable();
            $table->bigInteger('workflow_id')->nullable();
            $table->mediumText('initiator')->nullable();
            $table->mediumText('finisher')->nullable();
            $table->boolean('one_step_movement')->nullable();
            $table->mediumText('remarks')->nullable();
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
        Schema::dropIfExists('ulb_workflow_masters');
    }
}

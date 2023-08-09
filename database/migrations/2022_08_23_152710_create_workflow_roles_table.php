<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ulb_workflow_roles', function (Blueprint $table) {
            $table->id();
            $table->integer('ulb_workflow_id');
            $table->integer('role_id');
            $table->integer('forward_id');
            $table->integer('backward_id');
            $table->boolean('show_full_list')->nullable();
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
        Schema::dropIfExists('workflow_roles');
    }
}

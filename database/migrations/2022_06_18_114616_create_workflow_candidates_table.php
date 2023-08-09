<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowCandidatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_candidates', function (Blueprint $table) {
            $table->id();
            $table->integer('ulb_workflow_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('forward_id')->nullable();
            $table->integer('backward_id')->nullable();
            $table->boolean('full_movement')->nullable();  // If this field is true then one can forward everyone otherwise he can forward only 2 persons(forward and backward) 
            $table->boolean('is_admin')->nullable();
            $table->timestamp('deleted_at')->nullable();
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
        Schema::dropIfExists('workflow_candidates');
    }
}

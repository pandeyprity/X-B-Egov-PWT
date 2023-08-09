<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('workflow_tracks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('workflow_candidate_id');
            $table->bigInteger('citizen_id');
            $table->bigInteger('module_id');
            $table->mediumText('ref_table_dot_id')->nullable();         //eg- self.renewalid
            $table->mediumText('ref_table_id_value')->nullable();
            $table->mediumText('message')->nullable();
            $table->dateTime('track_date')->nullable();
            $table->integer('forwarded_to')->nullable();
            $table->boolean('deleted')->nullable();
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
        Schema::dropIfExists('workflow_tracks');
    }
}

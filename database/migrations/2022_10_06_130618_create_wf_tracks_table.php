<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wf_tracks', function (Blueprint $table) {
            $table->id();
            $table->integer('workflow_id');
            $table->integer('user_id');
            $table->dateTime('tran_time');
            $table->integer('ref_key');
            $table->integer('ref_id');
            $table->string('forward_id');
            $table->boolean('waiting_for_citizen');
            $table->mediumText('message');
            $table->smallInteger('status')->nullable()->default(1);
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
        Schema::dropIfExists('wf_tracks');
    }
}

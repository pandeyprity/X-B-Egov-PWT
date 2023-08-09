<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wf_masters', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_name');
            $table->boolean('is_suspended');
            $table->integer('user_id');
            $table->smallInteger('status')->nullable()->default(1);
            $table->dateTime('stamp_date_time');
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
        Schema::dropIfExists('wf_masters');
    }
}

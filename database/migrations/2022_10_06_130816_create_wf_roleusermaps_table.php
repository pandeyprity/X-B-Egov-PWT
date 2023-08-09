<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfRoleusermapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wf_roleusermaps', function (Blueprint $table) {
            $table->id();
            $table->integer('wf_role_id');
            $table->integer('user_id');
            $table->boolean('is_suspended');
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
        Schema::dropIfExists('wf_roleusermaps');
    }
}

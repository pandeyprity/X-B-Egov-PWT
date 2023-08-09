<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObjectionTypeMstrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('objection_type_mstrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 100)->nullable();
            $table->bigInteger('workflow_id')->nullable();
            $table->bigInteger('ulb_id')->nullable();
            $table->smallInteger('status')->nullable()->default(1);
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
        Schema::dropIfExists('objection_type_mstrs');
    }
}

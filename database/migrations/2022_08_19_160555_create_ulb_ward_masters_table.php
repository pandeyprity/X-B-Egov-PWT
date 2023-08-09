<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUlbWardMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ulb_ward_masters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ulb_id');
            $table->string('ward_name', 100);
            $table->string('old_ward_name', 100);
            $table->softDeletes();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ulb_ward_masters');
    }
}

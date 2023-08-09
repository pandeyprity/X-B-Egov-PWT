<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('ulb_id')->nullable();
            $table->mediumText('role_name')->nullable();
            $table->mediumText('role_description')->nullable();
            $table->mediumText('routes')->nullable();
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
        Schema::dropIfExists('role_masters');
    }
}

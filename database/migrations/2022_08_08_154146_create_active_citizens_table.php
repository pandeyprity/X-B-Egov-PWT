<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveCitizensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_citizens', function (Blueprint $table) {
            $table->id();
            $table->mediumText('user_name')->nullable();
            $table->mediumText('mobile')->nullable();
            $table->string('email', 254)->nullable();
            $table->mediumText('user_type')->nullable();
            $table->integer('ulb_id')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_approved')->nullable();
            $table->integer('created_by')->nullable();
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
        Schema::dropIfExists('active_citizens');
    }
}

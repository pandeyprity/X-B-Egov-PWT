<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('serial')->nullable();
            $table->mediumText('description')->nullable();
            $table->mediumText('menu_string')->nullable();
            $table->integer('parent_serial')->nullable();
            $table->mediumText('route')->nullable();
            $table->mediumText('icon')->nullable();
            $table->smallInteger('top_level')->nullable();
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
        Schema::dropIfExists('menu_masters');
    }
}

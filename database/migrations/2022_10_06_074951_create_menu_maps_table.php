<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_maps', function (Blueprint $table) {
            $table->id();
            $table->integer('ulb_menuroleid')->nullable;
            $table->integer('menu_itemid')->nullable;
            $table->boolean('general_permission')->nullable;
            $table->boolean('admin_permission')->nullable;
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
        Schema::dropIfExists('menu_maps');
    }
};

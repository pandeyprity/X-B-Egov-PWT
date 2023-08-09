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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->integer('serial')->nullable;
            $table->integer('menu_groupid')->nullable;
            $table->integer('parent_id')->nullable;
            $table->text('menu_name')->nullable;
            $table->text('display_string')->nullable;
            $table->text('icon_name')->nullable;
            $table->text('component_name')->nullable;
            $table->boolean('deleated')->nullable;
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
        Schema::dropIfExists('menu_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUlbMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ulb_masters', function (Blueprint $table) {
            $table->id();
            $table->mediumText('ulb_name')->nullable();
            $table->mediumText('ulb_type')->nullable();
            $table->bigInteger('city_id')->nullable();
            $table->mediumText('remarks')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->date('incorporation_date')->nullable();
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
        Schema::dropIfExists('ulb_masters');
    }
}

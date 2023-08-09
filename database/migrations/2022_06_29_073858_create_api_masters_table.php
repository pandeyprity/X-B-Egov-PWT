<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_masters', function (Blueprint $table) {
            $table->id();
            $table->mediumText('description')->nullable();
            $table->mediumText('remarks')->nullable();
            $table->mediumText('tags')->nullable();
            $table->mediumText('category')->nullable();
            $table->mediumText('end_point')->nullable();
            $table->mediumText('usage')->nullable();
            $table->mediumText('pre_condition')->nullable();
            $table->mediumText('request_payload')->nullable();         // IN JSON
            $table->mediumText('response_payload')->nullable();        // IN JSON
            $table->mediumText('post_condition')->nullable();
            $table->mediumText('version')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->mediumText('created_by')->nullable();
            $table->smallInteger('revision_no')->nullable();
            $table->boolean('discontinued')->nullable();
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
        Schema::dropIfExists('api_masters');
    }
}

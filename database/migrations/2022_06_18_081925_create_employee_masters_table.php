<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id')->nullable();
            $table->mediumText('employee_name')->nullable();
            $table->mediumText('mobile')->nullable();
            $table->mediumText('email')->nullable();
            $table->mediumText('address')->nullable();
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
        Schema::dropIfExists('employee_masters');
    }
}

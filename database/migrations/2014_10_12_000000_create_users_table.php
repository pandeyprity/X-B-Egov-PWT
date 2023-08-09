<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->mediumText('user_name')->nullable();
            $table->mediumText('mobile')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->mediumText('user_type')->nullable();
            $table->integer('roll_id')->nullable();
            $table->integer('ulb_id')->nullable();
            $table->string('password');
            $table->boolean('suspended')->nullable();
            $table->boolean('super_user')->nullable();
            $table->mediumText('description')->nullable();
            $table->mediumText('workflow_participant')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSingleWindowMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('single_window_masters', function (Blueprint $table) {
            $table->id();
            $table->mediumText('UniqueID')->nullable();
            $table->mediumText('FirstName')->nullable();
            $table->mediumText('LastName')->nullable();
            $table->mediumText('ContactPerson')->nullable();
            $table->mediumText('ContactPersonEmail')->nullable();
            $table->mediumText('ContactPersonMobile')->nullable();
            $table->integer('CustID')->nullable();
            $table->integer('DepartmentID')->nullable();
            $table->mediumText('PassKey')->nullable();
            $table->mediumText('CafUniqueNo')->nullable();
            $table->mediumText('IndustryUndertaking')->nullable();
            $table->integer('ServiceID')->nullable();
            $table->mediumText('PromoterName')->nullable();
            $table->mediumText('Designation')->nullable();
            $table->mediumText('RelationTypeName')->nullable();
            $table->mediumText('CommAddress1')->nullable();
            $table->mediumText('CommAddress2')->nullable();
            $table->mediumText('CommPinCode')->nullable();
            $table->mediumText('CommTelephoneNo')->nullable();
            $table->mediumText('CommEmail')->nullable();
            $table->mediumText('AddressLine1')->nullable();
            $table->mediumText('AddressLine2')->nullable();
            $table->mediumText('PinCode')->nullable();
            $table->mediumText('IndustryTelephoneNo')->nullable();
            $table->mediumText('Mobile')->nullable();
            $table->mediumText('Email')->nullable();
            $table->mediumText('IndustryApprovalID')->nullable();
            $table->integer('DistrictID')->nullable();
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
        Schema::dropIfExists('single_window_masters');
    }
}

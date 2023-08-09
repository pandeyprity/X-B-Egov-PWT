<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->mediumText('Circle')->nullable();
            $table->mediumText('LicenseHolderName')->nullable();
            $table->mediumText('LicenseHolderName1')->nullable();
            $table->mediumText('LicenseHolderFather')->nullable();
            $table->mediumText('LicenseHolderFather1')->nullable();
            $table->mediumText('LicenseHolderAddress')->nullable();
            $table->mediumText('LicenseHolderAddress1')->nullable();
            $table->mediumText('LicenseHolder1Address')->nullable();
            $table->mediumText('LicenseHolderMobile')->nullable();
            $table->mediumText('LicenseHolder1Mobile')->nullable();
            $table->mediumText('AllotmentNo')->nullable();
            $table->date('AllotmentDate')->nullable();
            $table->mediumText('BillTerm')->nullable();
            $table->mediumText('ShopName')->nullable();
            $table->mediumText('ShopNo')->nullable();
            $table->mediumText('PlotNo')->nullable();
            $table->mediumText('BuildingType')->nullable();
            $table->mediumText('Floor')->nullable();
            $table->integer('Area')->nullable();
            $table->decimal('RatePerSqFt', $precision = 18, $scale = 2)->nullable();
            $table->decimal('FlatAnnualRate', $precision = 18, $scale = 2)->nullable();
            $table->decimal('OpeningBalance', $precision = 18, $scale = 2)->nullable();
            $table->decimal('SecurityMoney', $precision = 18, $scale = 2)->nullable();
            $table->mediumText('CurrentOccupant')->nullable();
            $table->mediumText('OccupantAddress')->nullable();
            $table->string('OccupantMobile', 10)->nullable();
            $table->mediumText('IdentificationProof')->nullable();
            $table->mediumText('IDNo')->nullable();
            $table->mediumText('OwnerPhotograph')->nullable();
            $table->mediumText('PropertyLocation')->nullable();
            $table->mediumText('Longitude')->nullable();
            $table->mediumText('Latitude')->nullable();
            $table->mediumText('LegalIssueRemarks')->nullable();
            $table->mediumText('Email')->nullable();
            $table->mediumText('PAN')->nullable();
            $table->mediumText('GST')->nullable();
            $table->mediumText('ArrearPeriod')->nullable();
            $table->decimal('Arrear', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearGSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearGSTAmount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearInterestPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('ArrearInterestAmount', $precision = 18, $scale = 2)->nullable();
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
        Schema::dropIfExists('shops');
    }
}

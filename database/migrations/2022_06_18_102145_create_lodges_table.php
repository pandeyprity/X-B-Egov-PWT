<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLodgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lodges', function (Blueprint $table) {
            $table->id();
            $table->string('RenewalID', 15)->nullable();
            $table->string('UniqueID', 15)->nullable();
            $table->string('OldRenewalID', 15)->nullable();
            $table->smallInteger('Renewal')->nullable();
            $table->smallInteger('IsLodge')->nullable();
            $table->string('LicenseYear', 7)->nullable();
            $table->mediumText('EntityName')->nullable();
            $table->mediumText('EntityAddress')->nullable();
            $table->mediumText('EntityWard')->nullable();
            $table->mediumText('HoldingNo')->nullable();
            $table->mediumText('TradeLicenseNo')->nullable();
            $table->mediumText('Longitude')->nullable();
            $table->mediumText('Latitude')->nullable();
            $table->string('MobileNo', 10)->nullable();
            $table->mediumText('Email')->nullable();
            $table->mediumText('Applicant')->nullable();
            $table->mediumText('Father')->nullable();
            $table->mediumText('ResidenceAddress')->nullable();
            $table->mediumText('WardNo')->nullable();
            $table->mediumText('PermanentAddress')->nullable();
            $table->mediumText('WardNo1')->nullable();
            $table->mediumText('OrganizationType')->nullable();
            $table->mediumText('LandDeedType')->nullable();
            $table->mediumText('WaterSupplyType')->nullable();
            $table->mediumText('ElectricityType')->nullable();
            $table->mediumText('SecurityType')->nullable();
            $table->mediumText('LodgeType')->nullable();
            $table->mediumText('MessType')->nullable();
            $table->integer('Inhabitants')->nullable();
            $table->integer('Rooms')->nullable();
            $table->integer('FireExtinguisher')->nullable();
            $table->integer('NoOfCCTVCamera')->nullable();
            $table->integer('EntryGates')->nullable();
            $table->integer('ExitGates')->nullable();
            $table->integer('ParkingPlaceTwoWheeler')->nullable();
            $table->integer('ParkingPlaceFourWheeler')->nullable();
            $table->mediumText('AadharCardNo')->nullable();
            $table->mediumText('PanCardNo')->nullable();
            $table->mediumText('FrontagePhotoPath')->nullable();
            $table->mediumText('AadharPath')->nullable();
            $table->mediumText('BuildingPlanPath')->nullable();
            $table->mediumText('SolidWastePath')->nullable();
            $table->mediumText('HoldingTaxPath')->nullable();
            $table->mediumText('FireExtinguisherPath')->nullable();
            $table->mediumText('CCTVCameraPath')->nullable();
            $table->mediumText('NamePlateMobilePath')->nullable();
            $table->mediumText('EntryExitPath')->nullable();
            $table->string('Zone', 10)->nullable();
            $table->mediumText('Proceeding1Photo')->nullable();
            $table->mediumText('Proceeding2Photo')->nullable();
            $table->mediumText('Proceeding3Photo')->nullable();
            $table->mediumText('ExtraDoc1')->nullable();
            $table->mediumText('ExtraDoc2')->nullable();
            $table->integer('WorkflowID')->nullable();
            $table->mediumText('CurrentUser')->nullable();
            $table->mediumText('Initiator')->nullable();
            $table->mediumText('Approver')->nullable();
            $table->smallInteger('Pending')->nullable();
            $table->smallInteger('Approved')->nullable();
            $table->dateTime('ApprovalDate')->nullable();
            $table->smallInteger('Rejected')->nullable();
            $table->smallInteger('Paid')->nullable();
            $table->mediumText('RejectionReason')->nullable();
            $table->mediumText('ApplicationStatus')->nullable();

            $table->decimal('LicenseFee', $precision = 18, $scale = 2)->nullable();
            $table->decimal('Amount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('NetAmount', $precision = 18, $scale = 2)->nullable();

            $table->integer('OnlinePaymentID')->nullable();
            $table->mediumText('PmtMode')->nullable();
            $table->mediumText('Bank')->nullable();
            $table->mediumText('MRNo')->nullable();
            $table->mediumText('DraftNo')->nullable();
            $table->dateTime('PaymentDate')->nullable();
            $table->dateTime('DraftDate')->nullable();
            $table->string('CreatedOn', 29)->nullable();
            $table->integer('ModifiedBy')->nullable();
            $table->string('ModifiedOn', 29)->nullable();
            $table->mediumText('SignaturePath')->nullable();

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
        Schema::dropIfExists('lodges');
    }
}

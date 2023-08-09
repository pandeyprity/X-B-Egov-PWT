<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBandobastisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bandobastis', function (Blueprint $table) {
            $table->id();
            $table->mediumText('BandobastiName')->nullable();
            $table->mediumText('BandobastiAddress')->nullable();
            $table->mediumText('BandobastiType')->nullable();
            $table->mediumText('EntityName')->nullable();
            $table->mediumText('AllotteeName')->nullable();
            $table->mediumText('FatherName')->nullable();
            $table->mediumText('RAddress')->nullable();
            $table->mediumText('PAddress')->nullable();
            $table->mediumText('MobileNo')->nullable();
            $table->mediumText('AadharNo')->nullable();
            $table->mediumText('PanNo')->nullable();
            $table->mediumText('AllotmentNo')->nullable();
            $table->date('AllotmentFromDate')->nullable();
            $table->date('AllotmentToDate')->nullable();
            $table->decimal('GSTPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('TCPercent', $precision = 18, $scale = 2)->nullable();
            $table->decimal('AnnualIncrement', $precision = 18, $scale = 2)->nullable();
            $table->decimal('SecurityMoney', $precision = 18, $scale = 2)->nullable();
            $table->decimal('AllotmentAmount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GSTAmount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('TCAmount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('TotalAmount', $precision = 18, $scale = 2)->nullable();
            $table->smallInteger('Closed')->nullable();
            $table->mediumText('ClosingRemarks')->nullable();
            $table->mediumText('FolderPath')->nullable();
            $table->mediumText('AgreementPath')->nullable();
            $table->mediumText('AadharPath')->nullable();
            $table->mediumText('PANPath')->nullable();
            $table->mediumText('AuthorityLetterPath')->nullable();
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
        Schema::dropIfExists('bandobastis');
    }
}

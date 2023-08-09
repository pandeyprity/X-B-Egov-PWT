<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcAvenuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cc_avenues', function (Blueprint $table) {
            $table->id();
            $table->mediumText('UniqueID')->nullable();
            $table->string('TranDate', 29)->nullable();
            $table->decimal('Amount', $precision = 18, $scale = 2)->nullable();
            $table->mediumText('TrackingID')->nullable();
            $table->mediumText('BankRefNo')->nullable();
            $table->smallInteger('SelfAdvertisement')->nullable();
            $table->smallInteger('BanquetHall')->nullable();
            $table->smallInteger('Dharamshala')->nullable();
            $table->smallInteger('Hoarding')->nullable();
            $table->smallInteger('Lodge')->nullable();
            $table->smallInteger('PLAAdvertisement')->nullable();
            $table->smallInteger('VehicleAdvertisement')->nullable();
            $table->smallInteger('Agency')->nullable();
            $table->smallInteger('StatusSuccess')->nullable();
            $table->smallInteger('StatusFailure')->nullable();
            $table->smallInteger('StatusAborted')->nullable();
            $table->smallInteger('StatusInvalid')->nullable();
            $table->smallInteger('StatusTempered')->nullable();
            $table->smallInteger('Closed')->nullable();
            $table->mediumText('FailureReason')->nullable();
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
        Schema::dropIfExists('cc_avenues');
    }
}

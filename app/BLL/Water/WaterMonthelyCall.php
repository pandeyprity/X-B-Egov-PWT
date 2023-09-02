<?php

namespace App\BLL\Water;

use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterParamDemandCharge;
use App\Models\Water\WaterParamFreeUnit;
use App\Models\Water\WaterSecondConsumer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On :- 29-08-2023 
 * | Author     :- Sam kerketta
 * | Status     :- Open/ and will not closed 
 */
class WaterMonthelyCall
{
    private $_consumerId;
    private $_mWaterConsumer;
    private $_mWaterParamDemandCharge;
    private $_mWaterParamFreeUnit;
    private $_consumerCharges;
    private $_consumerFeeUnits;
    private $_consuemrDetails;
    private $_mWaterConsumerDemand;
    private $_consumerLastDemand;
    private $_toDate;
    private $_now;
    private $_consumerLastUnpaidDemand;
    private $_mWaterConsumerInitialMeter;
    private $_unitConsumed;
    private $_tax;
    private $_consumerLastMeterReding;
    private $_catagoryType;
    private $_meterStatus;
    # Class cons
    public function __construct(int $consumerId = 0, $toDate, $unitConsumed)
    {
        $this->_unitConsumed            = $unitConsumed ?? 0;
        $this->_consumerId              = $consumerId;
        $this->_toDate                  = $toDate;
        $this->_now                     = Carbon::now();
        $this->_catagoryType            = Config::get('waterConstaint.AKOLA_CATEGORY');
        $this->_mWaterConsumer          = new WaterSecondConsumer();
        $this->_mWaterParamDemandCharge = new WaterParamDemandCharge();
        $this->_mWaterParamFreeUnit     = new WaterParamFreeUnit();
        $this->_mWaterConsumerDemand    = new WaterConsumerDemand();
        $this->_mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
    }


    /**
     * | Get 
     */
    public function parentFunction()
    {
        $this->readParamsForCall();                 // 1
        $this->monthelyDemandCall();                // 2
        $this->generateDemand();                    // 3
        return $this->_tax;
    }

    /**
     * | params for calculation 
     */
    public function readParamsForCall()
    {
        $catagory = collect($this->_catagoryType)->flip();
        # Check the existence of consumer 
        $this->_consuemrDetails = $this->_mWaterConsumer->getConsumerDetailsById($this->_consumerId)
            ->where('status', 1)                                                                            // Static
            ->first();

        if ($this->_consuemrDetails->category == $catagory['1']) {
            $catagoryId = $this->_catagoryType['Slum'];
        } else {
            $catagoryId = $this->_catagoryType['General'];
        }
        # Get the charges for call 
        $chargesParams = new Request([
            "propertyType"      => $this->_consuemrDetails->property_type_id,
            "areaCatagory"      => $catagoryId,
            "connectionSize"    => $this->_consuemrDetails->tab_size,
            "meterState"        => $this->_consuemrDetails->is_meter_working
        ]);
        $this->_consumerCharges         = $this->_mWaterParamDemandCharge->getConsumerCharges($chargesParams);
        $this->_consumerFeeUnits        = $this->_mWaterParamFreeUnit->getFeeUnits($chargesParams);
        $this->_consumerLastDemand      = $this->_mWaterConsumerDemand->akolaCheckConsumerDemand($this->_consumerId)->first();
        $this->_consumerLastMeterReding = $this->_mWaterConsumerInitialMeter->getmeterReadingAndDetails($this->_consumerId)->orderByDesc('id')->first();
    }

    /**
     * | Consumer callculation 
     */
    public function monthelyDemandCall()
    {
        switch ($this->_consumerId) {
            case (!$this->_consuemrDetails):
                throw new Exception("consumer Details not found!");
                break;

            case (!$this->_consumerCharges):
                throw new Exception("consumer charges not found!");
                break;

            case (!$this->_consumerFeeUnits):
                throw new Exception("consumer free units not found!");
                break;
        }

        # Charges for month 
        $lastDemandDate     = $this->_consumerLastDemand->demand_upto ?? $this->_consuemrDetails->connection_date;
        if (!$lastDemandDate) {
            throw new Exception("Demand date not Found!");
        }

        $lastDemandMonth    = Carbon::parse($lastDemandDate)->format('Y-m');
        $currentMonth       = Carbon::parse($this->_now)->format('Y-m');

        # Check if the last demand is generated for the month
        if (!$lastDemandDate) {
            if ($lastDemandMonth >= $currentMonth) {
                throw new Exception("demand is generated till $lastDemandDate!");
            }
        }
        if ($this->_consuemrDetails->is_meter_working == 1) {
            $this->_meterStatus = "Meter";                                                                   // Static
            if ($this->_unitConsumed < ($this->_consumerLastMeterReding->initial_reading ?? 0)) {                                 // Static
                throw new Exception("finalRading should be grater than previous reading!");
            }
        }
        if ($this->_consuemrDetails->is_meter_working == 0) {
            $this->_meterStatus = "Fixed";
        }
    }

    /**
     * | Generta demand 
     */
    public function generateDemand()
    {
        if ($this->_consumerLastDemand) {
            $endDate            = Carbon::parse($this->_now)->firstOfMonth();
            $startDate          = Carbon::parse($this->_consumerLastDemand->demand_from)->firstOfMonth();
            $monthsDifference   = $startDate->diffInMonths($endDate);

            # get all the month between dates
            $monthsArray = [];
            $currentDate = $startDate->copy();
            while ($currentDate->lt($endDate)) {
                $monthsArray[] = $currentDate->format('Y-m-01');
                $currentDate->addMonth();
            }

            # calculate 
            if ($monthsDifference > 0) {
                $monthelyUnitConsumed = ($this->_unitConsumed - $this->_consumerLastMeterReding->initial_reading) / $monthsDifference;
            } else {
                $monthelyUnitConsumed = ($this->_unitConsumed - $this->_consumerLastMeterReding->initial_reading);
            }

            # demand generation
            $returnData = collect($monthsArray)->map(function ($values, $key)
            use ($monthelyUnitConsumed, $monthsDifference) {

                $lastDateOfMonth = Carbon::parse($values . '-01')->endOfMonth();
                $amount = $this->_consumerFeeUnits->unit_fee * ($monthelyUnitConsumed - 10);                                // Static
                if ($amount < 0) {
                    $amount = 0;
                }
                return [
                    "generation_date"       => $this->_now,
                    "amount"                => $amount,
                    "current_meter_reading" => $this->_unitConsumed,
                    "unit_amount"           => 1,                                           // Statisc
                    "demand_from"           => $values . "-01",                              // Static
                    "demand_upto"           => $lastDateOfMonth->format('Y-m-d'),
                    "connection_type"       => $this->_meterStatus,
                ];
            });

            $this->_tax = [
                "status" => true,
                "consumer_tax" => [
                    [
                        "charge_type"       => $this->_meterStatus,
                        "rate_id"           => $this->_consumerCharges->id,
                        "effective_from"    => $startDate->format('Y-m-d'),
                        "initial_reading"   => $this->_consumerLastMeterReding->initial_reading,
                        "final_reading"     => $this->_unitConsumed,
                        "amount"            => $returnData->sum('amount'),
                        "consumer_demand"   => $returnData->toArray(),
                    ]
                ]
            ];
        } else {
            $endDate            = Carbon::parse($this->_now)->firstOfMonth();
            $startDate          = Carbon::parse($this->_consuemrDetails->connection_date)->firstOfMonth();
            $monthsDifference   = $startDate->diffInMonths($endDate);

            # get all the month between dates
            $monthsArray = [];
            $currentDate = $startDate->copy();
            while ($currentDate->lt($endDate)) {
                $monthsArray[] = $currentDate->format('Y-m-01');
                $currentDate->addMonth();
            }

            # calculate 
            if ($monthsDifference > 0) {
                $monthelyUnitConsumed = ($this->_unitConsumed - $this->_consumerLastMeterReding->initial_reading) / $monthsDifference;
            } else {
                $monthelyUnitConsumed = ($this->_unitConsumed - ($this->_consumerLastMeterReding->initial_reading ?? 0));
            }

            # demand generation
            $returnData = collect($monthsArray)->map(function ($values, $key)
            use ($monthelyUnitConsumed, $monthsDifference) {

                $lastDateOfMonth = Carbon::parse($values . '-01')->endOfMonth();
                $amount = $this->_consumerFeeUnits->unit_fee * ($monthelyUnitConsumed - 10);                                // Static
                if ($amount < 0) {
                    $amount = 0;
                }
                return [
                    "generation_date"       => $this->_now,
                    "amount"                => $amount,
                    "current_meter_reading" => $this->_unitConsumed,
                    "unit_amount"           => 1,                                           // Statisc
                    "demand_from"           => $values . "-01",                              // Static
                    "demand_upto"           => $lastDateOfMonth->format('Y-m-d'),
                    "connection_type"       => $this->_meterStatus,

                ];
            });

            if (empty($monthsArray)) {
                if ($monthsDifference == 0) {
                    $amount = $this->_consumerFeeUnits->unit_fee * ($monthelyUnitConsumed - 10);                                // Static
                    if ($amount < 0) {
                        $amount = 0;
                    }
                    $refArray = [
                        "generation_date"       => $this->_now->format('Y-m-d'),
                        "amount"                => $amount,
                        "current_meter_reading" => $this->_unitConsumed,
                        "unit_amount"           => 1,                                           // Statisc
                        "demand_from"           => $endDate->format('Y-m-d'),                                    // Static
                        "demand_upto"           => $this->_now->endOfMonth()->format('Y-m-d'),
                        "connection_type"       => $this->_meterStatus,
                    ];
                    $returnData = new Collection($refArray);
                }
            }

            # Return details
            $refAmount = $amount ?? $returnData->pluck('amount')->sum();
            $this->_tax = [
                "status" => true,
                "consumer_tax" => [
                    [
                        "charge_type"       => $this->_meterStatus,
                        "rate_id"           => $this->_consumerCharges->id,
                        "effective_from"    => $startDate->format('Y-m-d'),
                        "initial_reading"   => $this->_consumerLastMeterReding->initial_reading ?? 0,
                        "final_reading"     => $this->_unitConsumed,
                        "amount"            => $refAmount,
                        "consumer_demand"   => $returnData->toArray(),

                    ]
                ]
            ];
        }
    }
}

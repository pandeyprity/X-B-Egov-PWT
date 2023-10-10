<?php

namespace App\BLL\Water;

use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterParamDemandCharge;
use App\Models\Water\WaterParamFreeUnit;
use App\Models\Water\WaterSecondConsumer;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On :- 29-08-2023 
 * | Author     :- Sam kerketta
 * | Status     :- Semi Closed
 * | Calculation of the consumer demand according to AMC  
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
    private $_mWaterConsumerMeter;
    private $_consuemrMeterDetails;
    # Class cons
    public function __construct(int $consumerId, $toDate, $unitConsumed)
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
        $this->_mWaterConsumerMeter     = new WaterConsumerMeter();
    }


    /**
     * | Parent function 
     * | Distribution of the calculation process into function 
     */
    public function parentFunction()
    {
        $this->readParamsForCall();                     // 1
        $this->monthelyDemandCall();                    // 2
        $this->generateDemand();                        // 3
        return $this->_tax;
    }

    /**
     * | params assigning for calculation 
     * | Get all the params and data from database
        | Collect the consumer meter details       
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

        # Assigning the global var 
        $this->_consumerCharges         = $this->_mWaterParamDemandCharge->getConsumerCharges($chargesParams);
        $this->_consumerFeeUnits        = $this->_mWaterParamFreeUnit->getFeeUnits($chargesParams);
        $this->_consumerLastDemand      = $this->_mWaterConsumerDemand->akolaCheckConsumerDemand($this->_consumerId)->first();
        $this->_consumerLastMeterReding = $this->_mWaterConsumerInitialMeter->getmeterReadingAndDetails($this->_consumerId)->orderByDesc('id')->first();
        $this->_consuemrMeterDetails    = $this->_mWaterConsumerMeter->getMeterDetailsByConsumerId($this->_consumerId)->firest();
    }

    /**
     * | Consumer calculation 
     * | Checking the params before calculation
        | Check the meter status for meter and non meter  
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
        $lastDemandDate = $this->_consumerLastDemand->demand_upto ?? $this->_consuemrDetails->connection_date;
        if (!$lastDemandDate) {
            throw new Exception("Demand date not Found!");
        }
        $lastDemandMonth    = Carbon::parse($lastDemandDate)->format('Y-m');
        $currentMonth       = Carbon::parse($this->_now->copy())->format('Y-m');

        # Check if the last demand is generated for the month
        if (!$lastDemandDate) {
            if ($lastDemandMonth >= $currentMonth) {
                throw new Exception("demand is generated till $lastDemandDate!");
            }
        }
        # ❗❗ Check the connection type for the consumer ❗❗
        if (!$this->_consuemrMeterDetails) {
            throw new Exception("update Connection detials!");
        }
        if ($this->_consuemrMeterDetails->connection_type == 1) {
            $this->_meterStatus = "Meter";                                                                                          // Static
            if ($this->_unitConsumed < ($this->_consumerLastMeterReding->initial_reading ?? 0)) {                                   // Static
                throw new Exception("finalRading should be grater than previous reading!");
            }
        }
        if ($this->_consuemrMeterDetails->connection_type == 3) {
            $this->_meterStatus = "Fixed";                                                                                          // Static
        }
    }

    /**
     * | Generta demand 
     * | Actual calculation
        | Check the comment 
     */
    public function generateDemand()
    {
        # Switch between meter connection and non meter connection 
        switch ($this->_meterStatus) {
            case ("Meter"):                                                                 // Static
                # If the consumer demand exist the following process will continue with respective of last demand
                if ($this->_consumerLastDemand) {
                    $monthsArray        = [];
                    $startDate          = Carbon::parse($this->_consumerLastDemand->demand_upto);
                    $endDate            = Carbon::parse($this->_now);
                    $monthsDifference   = $startDate->diffInMonths($endDate);
                    $dateDifference     = $startDate->diffInDays($endDate);
                    $daysInEndMonth     = $endDate->copy()->endOfMonth()->day;

                    # If the end date is the last day of the month, don't count it
                    if ($endDate->day === $daysInEndMonth) {
                        $monthsDifference--;
                        $dateCount = 0;
                    } else {
                        $dateCount = $endDate->day;
                    }

                    # get all the month between dates
                    if ($monthsDifference != 0) {
                        $currentDate = $startDate->copy();
                        while ($currentDate->lt($endDate)) {
                            $monthsArray[] = $currentDate->format('Y-m-01');
                            $currentDate->addMonth();
                        }
                    }

                    # daly consumed unit
                    $dalyUnitConsumed = ($this->_unitConsumed - $this->_consumerLastMeterReding->initial_reading) / $dateDifference;

                    # Monthely demand generation 
                    $returnData = collect($monthsArray)->map(function ($values, $key)
                    use ($dalyUnitConsumed) {
                        $lastDateOfMonth = Carbon::parse($values)->endOfMonth();
                        $noOfDays = $lastDateOfMonth->day;
                        $amount = ($this->_consumerFeeUnits->unit_fee * (($noOfDays * $dalyUnitConsumed) - 10)) + $this->_consumerCharges->amount;                        // Static the free amount per month
                        if ($amount < 0) {
                            $amount = 0;
                        }
                        return [
                            "generation_date"       => $this->_now,
                            "amount"                => $amount,
                            "current_meter_reading" => $this->_unitConsumed,
                            "unit_amount"           => 1,                                           // Statisc
                            "demand_from"           => $values,                                     // Static
                            "demand_upto"           => $lastDateOfMonth->format('Y-m-d'),
                            "connection_type"       => $this->_meterStatus,
                        ];
                    });

                    # If the day diff exist
                    if ($dateCount > 0) {
                        $demandFrom = $endDate->copy()->startOfMonth()->format('Y-m-d');
                        $amount = $this->_consumerFeeUnits->unit_fee * ($dateCount * $dalyUnitConsumed);
                        if ($amount < 0) {
                            $amount = 0;
                        }
                        $dateWiseCall[] = [
                            "generation_date"       => $this->_now,
                            "amount"                => $amount,
                            "current_meter_reading" => $this->_unitConsumed,
                            "unit_amount"           => 1,                                               // Static
                            "demand_from"           => $demandFrom,                                     // Static
                            "demand_upto"           => $endDate->format('Y-m-d'),
                            "connection_type"       => $this->_meterStatus,
                        ];
                        $returnData = $returnData->merge(collect($dateWiseCall));
                    }

                    # show taxes
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
                }
                # If the demand is generated for the first time
                else {
                    $endDate            = Carbon::parse($this->_now);
                    $startDate          = Carbon::parse($this->_consuemrDetails->connection_date);
                    $monthsDifference   = $startDate->diffInMonths($endDate);
                    $dateDifference     = $startDate->diffInDays($endDate);
                    $daysInEndMonth     = $endDate->copy()->endOfMonth()->day;

                    # If the end date is the last day of the month, don't count it
                    if ($endDate->day === $daysInEndMonth) {
                        $monthsDifference--;
                        $dateCount = 0;
                    } else {
                        $dateCount = $endDate->day;
                    }

                    # get all the month between dates
                    if ($monthsDifference != 0) {
                        $monthsArray = [];
                        $currentDate = $startDate->copy();
                        while ($currentDate->lt($endDate)) {
                            $monthsArray[] = $currentDate->format('Y-m-01');
                            $currentDate->addMonth();
                        }
                    }

                    # daly consumed unit
                    $dalyUnitConsumed = ($this->_unitConsumed - $this->_consumerLastMeterReding->initial_reading) / $dateDifference;

                    # monthely Demand call 
                    $returnData = collect($monthsArray)->map(function ($values, $key)
                    use ($dalyUnitConsumed) {
                        $lastDateOfMonth = Carbon::parse($values)->endOfMonth();
                        $noOfDays = $lastDateOfMonth->day;
                        $amount = ($this->_consumerFeeUnits->unit_fee * (($noOfDays * $dalyUnitConsumed) - 10)) + $this->_consumerCharges->amount;                        // Static the free amount per month
                        if ($amount < 0) {
                            $amount = 0;
                        }
                        return [
                            "generation_date"       => $this->_now,
                            "amount"                => $amount,
                            "current_meter_reading" => $this->_unitConsumed,
                            "unit_amount"           => 1,                                           // Statisc
                            "demand_from"           => $values,                                     // Static
                            "demand_upto"           => $lastDateOfMonth->format('Y-m-d'),
                            "connection_type"       => $this->_meterStatus,
                        ];
                    });
                    # If the day diff exist
                    if ($dateCount > 0) {
                        $demandFrom = $endDate->copy()->startOfMonth()->format('Y-m-d');
                        $amount = $this->_consumerFeeUnits->unit_fee * ($dateCount * $dalyUnitConsumed);
                        if ($amount < 0) {
                            $amount = 0;
                        }
                        $dateWiseCall[] = [
                            "generation_date"       => $this->_now,
                            "amount"                => $amount,
                            "current_meter_reading" => $this->_unitConsumed,
                            "unit_amount"           => 1,                                               // Static
                            "demand_from"           => $demandFrom,                                     // Static
                            "demand_upto"           => $endDate->format('Y-m-d'),
                            "connection_type"       => $this->_meterStatus,
                        ];
                        $returnData = $returnData->merge(collect($dateWiseCall));
                    }

                    # Return details
                    $this->_tax = [
                        "status" => true,
                        "consumer_tax" => [
                            [
                                "fee_unit"          => $this->_consumerFeeUnits->unit_fee,
                                "charge_type"       => $this->_meterStatus,
                                "rate_id"           => $this->_consumerCharges->id,
                                "effective_from"    => $startDate->format('Y-m-d'),
                                "initial_reading"   => $this->_consumerLastMeterReding->initial_reading ?? 0,                   // Collect the details form water consumer meter
                                "final_reading"     => $this->_unitConsumed,
                                "amount"            => $returnData->sum('amount'),
                                "consumer_demand"   => $returnData->toArray(),
                            ]
                        ]
                    ];
                }
                break;

            case ("Fixed"):                                                                         // Static
                if ($this->_consumerLastDemand) {
                    $monthsArray        = [];
                    $endDate            = Carbon::parse($this->_now->copy())->endOfMonth();
                    $startDate          = ((Carbon::parse($this->_consumerLastDemand->demand_from))->firstOfMonth())->addMonth();

                    # get all the month between dates
                    $currentDate = $startDate->copy();
                    while ($currentDate->lt($endDate)) {
                        $monthsArray[] = $currentDate->format('Y-m-01');
                        $currentDate->addMonth();
                    }

                    # demand generation
                    $returnData = collect($monthsArray)->map(function ($values, $key) {
                        $lastDateOfMonth = Carbon::parse($values)->endOfMonth();
                        $amount = $this->_consumerCharges->amount;                                  // look over here
                        return [
                            "generation_date"       => $this->_now,
                            "amount"                => $amount,
                            "current_meter_reading" => $this->_unitConsumed,
                            "unit_amount"           => 1,                                           // Statisc
                            "demand_from"           => $values,                                     // Static
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
                }
                break;
            default:
                throw new Exception("Demand generation process works only for meter and non meter connection!");
                break;
        }
    }
}

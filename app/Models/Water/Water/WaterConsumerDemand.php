<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterConsumerDemand extends Model
{
    use HasFactory;
    /**
     * | Get Payed Consumer Demand
     * | @param ConsumerId
     */
    public function getDemandBydemandId($demandId)
    {
        return WaterConsumerDemand::where('id', $demandId)
            ->where('paid_status', 1)
            ->first();
    }


    /**
     * | Get Demand According to consumerId and payment status false 
        | Here Changes
     */
    public function getConsumerDemand($consumerId)
    {
        $this->impos_penalty($consumerId);
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('paid_status', 0)
            ->where('status', true)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | 
     */
    public function getRefConsumerDemand($consumerId)
    {
        $this->impos_penalty($consumerId);
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('status', true)
            ->orderByDesc('id');
    }



    public function consumerDemandByConsumerId($consumerId)
    {
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('status', true)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * | Deactivate the consumer Demand
     * | Demand Ids will be in array
     * | @param DemandIds
     */
    public function deactivateDemand($demandIds)
    {
        WaterConsumerDemand::whereIn('id', $demandIds)
            ->update([
                'status' => false
            ]);
    }


    /**
     * | Save the consumer demand while Demand generation
     * | @param demands
     * | @param meterDetails
        | Create the demand no through id generation
     */
    public function saveConsumerDemand($demands, $consumerDetails, $request, $taxId, $userDetails)
    {
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $mWaterConsumerDemand->consumer_id              =  $consumerDetails->id;
        $mWaterConsumerDemand->ward_id                  =  $consumerDetails->ward_mstr_id;
        $mWaterConsumerDemand->ulb_id                   =  $consumerDetails->ulb_id;
        $mWaterConsumerDemand->generation_date          =  $demands['generation_date'];
        $mWaterConsumerDemand->amount                   =  $demands['amount'];
        $mWaterConsumerDemand->paid_status              =  0;                                   // Static
        $mWaterConsumerDemand->consumer_tax_id          =  $taxId;
        $mWaterConsumerDemand->emp_details_id           =  $userDetails['emp_id'] ?? null;
        $mWaterConsumerDemand->citizen_id               =  $userDetails['citizen_id'] ?? null;
        $mWaterConsumerDemand->demand_from              =  $demands['demand_from'];
        $mWaterConsumerDemand->demand_upto              =  $demands['demand_upto'];
        $mWaterConsumerDemand->penalty                  =  $demands['penalty'] ?? 0;            // Static
        $mWaterConsumerDemand->current_meter_reading    =  $request->finalRading;
        $mWaterConsumerDemand->unit_amount              =  $demands['unit_amount'];
        $mWaterConsumerDemand->connection_type          =  $demands['connection_type'];
        $mWaterConsumerDemand->demand_no                =  "WCD" . random_int(100000, 999999) . "/" . random_int(1, 10);
        $mWaterConsumerDemand->balance_amount           =  $demands['penalty'] ?? 0 + $demands['amount'];
        $mWaterConsumerDemand->created_at               =  Carbon::now();
        $mWaterConsumerDemand->save();

        return $mWaterConsumerDemand->id;
    }


    /**
     * | Get Demand According to consumerId and payment status false 
     */
    public function getFirstConsumerDemand($consumerId)
    {
        $this->impos_penalty($consumerId);
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('paid_status', 0)
            ->where('status', true)
            ->orderByDesc('id');
    }


    /**
     * | impose penalty
     * 
     */
    public function impos_penalty($consumerId)
    {
        try {
            $fine_months = 0;
            $penalty = 0.00;
            $penalty_amt = 0.00;
            $demand = array();
            $currend_date = Carbon::now()->format("Y-m-d");
            $meter_demand_sql = "SELECT * FROM  water_consumer_demands 
                                    where consumer_id=$consumerId 
                                    and paid_status= 0
                                    and status=true 
                                    and connection_type in ('Metered', 'Meter')";

            $meter_demand = DB::select($meter_demand_sql);
            DB::beginTransaction();
            #meter Demand
            foreach ($meter_demand as $val) {
                $val = collect($val)->all();
                if ($val["panelty_updated_on"] == $currend_date) {
                    continue;
                }
                if ($val["demand_upto"] >= '2021-01-01') {
                    $fine_months_sql = "SELECT ((DATE_PART('year', '$currend_date'::date) - DATE_PART('year', '" . ($val["demand_upto"]) . "'::date)) * 12 +
                                            (DATE_PART('month', '$currend_date'::date) - DATE_PART('month', '" . ($val["demand_upto"]) . "'::date))) :: integer as months
                                            ";
                    $fine_months = ((DB::select($fine_months_sql))[0]->months) ?? 0;
                }
                if ($fine_months >= 3) {
                    $penalty = ($val["amount"] / 100) * 1.5;
                    $penalty_amt = ($penalty * ($fine_months - 2));
                    $upate_sql = "update water_consumer_demands  set penalty=" . ($penalty_amt) . ", 
                                    balance_amount=(" . ($val["amount"] + $penalty_amt) . "), 
                                    panelty_updated_on='" . $currend_date . "' 
                                    where id=" . $val["id"] . " ";
                    $id = DB::select($upate_sql);
                } else {
                    $upate_sql = "update water_consumer_demands  set penalty=" . $penalty . ", 
                                    balance_amount=(" . ($val["amount"] + $penalty_amt) . "), 
                                    panelty_updated_on='" . $currend_date . "' 
                                    where id=" . $val["id"] . "";
                    $id = DB::select($upate_sql);
                }
            }

            #fixed Demand
            $fixed_demand_sql = "SELECT * FROM water_consumer_demands  
                                    where consumer_id=$consumerId 
                                    and paid_status=0 
                                    and status=true 
                                    and connection_type='Fixed'";
            $fixed_demand = DB::select($fixed_demand_sql);
            foreach ($fixed_demand as $val) {
                $val = collect($val)->all();
                if ($val["panelty_updated_on"] == $currend_date) {
                    continue;
                }
                if ($val["demand_upto"] >= '2015-07-01') {
                    $fine_months_sql = "SELECT ((DATE_PART('year', '$currend_date'::date) - DATE_PART('year', '" . ($val["demand_upto"]) . "'::date)) * 12 +
                                            (DATE_PART('month', '$currend_date'::date) - DATE_PART('month', '" . ($val["demand_upto"]) . "'::date))) :: integer
                                            ";
                    $fine_months = ((DB::select($fine_months_sql))[0]->months) ?? 0;
                }
                if ($fine_months >= 1) {
                    $penalty = ($val["amount"] / 100) * 10;
                    $penalty_amt = $penalty;
                    $upate_sql = "update water_consumer_demands  set penalty=" . $penalty_amt . ", 
                                    balance_amount=(" . ($val["amount"] + $penalty_amt) . "), 
                                    panelty_updated_on='" . $currend_date . "' 
                                    where id=" . $val["id"] . " ";
                    DB::select($upate_sql);
                } else {
                    $upate_sql = "update water_consumer_demands  set penalty=" . $penalty . ", 
                                    balance_amount=(" . ($val["amount"] + $penalty_amt) . "), 
                                    panelty_updated_on='" . $currend_date . "' 
                                    where id=" . $val["id"] . "";
                    DB::select($upate_sql);
                }
            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * | Get collictively consumer demand by demand Ids
     * | @param ids
     */
    public function getDemandCollectively($ids)
    {
        return WaterConsumerDemand::whereIn('id', $ids)
            ->where('status', true)
            ->where('paid_status', 1);
    }

    /**
     * | get the meter listing
     */
    public function getConsumerTax($demandIds)
    {
        return WaterConsumerDemand::select(
            'water_consumer_taxes.initial_reading',
            "water_consumer_taxes.final_reading",
            'water_consumer_demands.*'
        )
            ->leftjoin('water_consumer_taxes', function ($join) {
                $join->on('water_consumer_taxes.id', 'water_consumer_demands.consumer_tax_id')
                    ->where('water_consumer_taxes.status', 1)
                    ->where('water_consumer_taxes.charge_type', 'Meter');
            })
            ->whereIn('water_consumer_demands.id', $demandIds)
            ->where('water_consumer_demands.status', 1)
            ->orderByDesc('water_consumer_demands.id')
            ->get();
    }


    /**
     * | Get Demand According to consumerId and payment status false 
        | Caution 
        | Use only to check consumer demand in case of online payment 
        | Dont use any where else 
     */
    public function checkConsumerDemand($consumerId)
    {
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('paid_status', 0)
            ->where('status', true)
            ->orderByDesc('id');
    }
}

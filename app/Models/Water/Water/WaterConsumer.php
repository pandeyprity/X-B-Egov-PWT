<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterConsumer extends Model
{
    use HasFactory;

    /**
     * | Save the approved application to water Consumer
     * | @param consumerDetails
     * | @return
     */
    public function saveWaterConsumer($consumerDetails, $consumerNo)
    {
        $mWaterConsumer = new WaterConsumer();
        $mWaterConsumer->apply_connection_id         = $consumerDetails['id'];
        $mWaterConsumer->connection_type_id          = $consumerDetails['connection_type_id'];
        $mWaterConsumer->connection_through_id       = $consumerDetails['connection_through'];
        $mWaterConsumer->pipeline_type_id            = $consumerDetails['pipeline_type_id'];
        $mWaterConsumer->property_type_id            = $consumerDetails['property_type_id'];
        $mWaterConsumer->prop_dtl_id                 = $consumerDetails['prop_id'];
        $mWaterConsumer->holding_no                  = $consumerDetails['holding_no'];
        $mWaterConsumer->saf_dtl_id                  = $consumerDetails['saf_id'];
        $mWaterConsumer->saf_no                      = $consumerDetails['saf_no'];
        $mWaterConsumer->category                    = $consumerDetails['category'];
        $mWaterConsumer->ward_mstr_id                = $consumerDetails['ward_id'];
        $mWaterConsumer->consumer_no                 = $consumerNo;
        $mWaterConsumer->address                     = $consumerDetails['address'];
        $mWaterConsumer->apply_from                  = $consumerDetails['apply_from'];
        $mWaterConsumer->k_no                        = $consumerDetails['elec_k_no'];
        $mWaterConsumer->bind_book_no                = $consumerDetails['elec_bind_book_no'];
        $mWaterConsumer->account_no                  = $consumerDetails['elec_account_no'];
        $mWaterConsumer->electric_category_type      = $consumerDetails['elec_category'];
        $mWaterConsumer->ulb_id                      = $consumerDetails['ulb_id'];
        $mWaterConsumer->area_sqft                   = $consumerDetails['area_sqft'];
        $mWaterConsumer->owner_type_id               = $consumerDetails['owner_type'];
        $mWaterConsumer->application_apply_date      = $consumerDetails['apply_date'];
        $mWaterConsumer->user_id                     = $consumerDetails['user_id'];
        $mWaterConsumer->pin                         = $consumerDetails['pin'];
        $mWaterConsumer->user_type                   = $consumerDetails['user_type'];
        $mWaterConsumer->area_sqmt                   = $consumerDetails['area_sqft'];
        $mWaterConsumer->approve_date                = Carbon::now();
        $mWaterConsumer->save();
        return $mWaterConsumer->id;
    }


    /**
     * | get the water consumer detaials by consumr No
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getDetailByConsumerNo($req, $key, $refNo)
    {
        return WaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicant_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->where('water_consumers.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('water_consumers.status', 1)
            ->where('water_consumers.ulb_id', authUser($req)->ulb_id)
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumers.id',
                'water_consumers.ulb_id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.ward_mstr_id',
                'ulb_ward_masters.ward_name'
            );
    }


    /**
     * | get the water consumer detaials by Owner details
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getDetailByOwnerDetails($key, $refVal)
    {
        return WaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            'water_consumer_owners.applicant_name as applicant_name',
            'water_consumer_owners.mobile_no as mobile_no',
            'water_consumer_owners.guardian_name as guardian_name',
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->where('water_consumer_owners.' . $key, 'LIKE', '%' . $refVal . '%')
            ->where('water_consumers.status', 1)
            ->where('ulb_ward_masters.status', true);
    }


    /**
     * | get the water consumer detaials by application no
     * | @param refVal as ApplicationNo
     */
    public function getDetailByApplicationNo($refVal)
    {
        return WaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            'water_consumer_owners.applicant_name as applicant_name',
            'water_consumer_owners.mobile_no as mobile_no',
            'water_consumer_owners.guardian_name as guardian_name',
        )
            ->join('water_approval_application_details', 'water_approval_application_details.id', 'water_consumers.apply_connection_id')
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->where('water_approval_application_details.application_no', 'LIKE', '%' . $refVal . '%')
            ->where('water_consumers.status', 1)
            ->where('ulb_ward_masters.status', true);
    }



    /**
     * | Get the list of Application according to user id
     * | @param 
     * | @var 
     * | @return 
            | not finshed
     */
    public function getConsumerDetails($req)
    {
        return WaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.apply_connection_id',
            'water_consumers.application_apply_date',
            'water_consumers.address',
            'water_consumers.ulb_id',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ward_mstr_id',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicant_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),
            'water_connection_charges.charge_category',
            'water_connection_charges.amount',
            'water_connection_charges.penalty',
            'water_connection_charges.conn_fee',
            'water_connection_charges.rule_set',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name'

        )
            ->join('ulb_masters', 'ulb_masters.id', 'water_consumers.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_consumers.ward_mstr_id')
            ->leftjoin('water_connection_charges', 'water_connection_charges.application_id', '=', 'water_consumers.apply_connection_id')
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->where('water_consumers.user_id', authUser($req)->id)
            ->where('water_consumers.user_type', authUser($req)->user_type)
            ->where('water_consumers.status', 1)
            // ->where('water_consumers.ulb_id', authUser($req)->ulb_id)
            ->groupBy(
                'water_consumers.id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.apply_connection_id',
                'water_consumers.application_apply_date',
                'water_consumers.address',
                'water_consumers.ulb_id',
                'water_consumers.holding_no',
                'water_consumers.saf_no',
                'water_consumers.ward_mstr_id',
                'water_connection_charges.application_id',
                'water_connection_charges.charge_category',
                'water_connection_charges.amount',
                'water_connection_charges.penalty',
                'water_connection_charges.conn_fee',
                'water_connection_charges.rule_set',
                'ulb_ward_masters.ward_name',
                'ulb_masters.ulb_name'
            )
            ->get();
    }


    /**
     * | get the water consumer detaials by consumr No / accurate search
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getConsumerByConsumerNo($key, $parameter)
    {
        return WaterConsumer::select(
            'water_consumers.*',
            'water_consumers.id as consumer_id',
            'ulb_ward_masters.ward_name',
            'water_consumers.connection_through_id',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'water_owner_type_mstrs.owner_type AS owner_char_type',
            'water_param_pipeline_types.pipeline_type'
        )
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_consumers.connection_through_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_consumers.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_consumers.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_consumers.property_type_id')
            ->join('water_owner_type_mstrs', 'water_owner_type_mstrs.id', '=', 'water_consumers.owner_type_id')
            ->leftjoin('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_consumers.pipeline_type_id')

            ->Join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->where('water_consumers.' . $key, $parameter)
            ->where('water_consumers.status', 1)
            ->firstOrFail();
    }

    /**
     * | Get Consumer Details By ApplicationId ie. the ID 
     * | @param consumerId
     */
    public function getConsumerListById($consumerId, $demandId)
    {
        return WaterConsumer::select(
            'water_consumers.id as consumerId',
            'water_consumers.consumer_no',
            'water_consumers.apply_connection_id',
            'water_consumers.application_apply_date',
            'water_consumers.address',
            'water_consumers.ulb_id',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumer_demands.*',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.old_ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as consumer_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),

        )
            ->Join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_consumers.ulb_id')
            ->leftjoin('water_consumer_demands', 'water_consumer_demands.consumer_id', '=', 'water_consumers.id')
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->where('water_consumers.id', $consumerId)
            ->where('water_consumer_demands.id', $demandId)
            ->where('water_consumers.status', 1)
            ->where('water_consumer_demands.status', true)
            ->where('water_consumer_owners.status', true)
            ->groupBy(
                'water_consumers.id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.apply_connection_id',
                'water_consumers.application_apply_date',
                'water_consumers.address',
                'water_consumers.ulb_id',
                'water_consumers.holding_no',
                'water_consumers.saf_no',
                'water_consumer_demands.consumer_id',
                'water_consumer_demands.id',
                'ulb_masters.id',
                'ulb_masters.ulb_name',
                'ulb_ward_masters.id',
                'ulb_ward_masters.old_ward_name'
            )
            ->firstOrFail();
    }

    /**
     * | Get consumer Details By ConsumerId
     * | @param conasumerId
     */
    public function getConsumerDetailById($consumerId)
    {
        return WaterConsumer::where('id', $consumerId)
            ->where('status', 1)
            ->firstOrFail();
    }


    /**
     * | Dectivate the water Consumer 
     * | @param req
     */
    public function dissconnetConsumer($consumerId, $status)
    {
        WaterConsumer::where('id', $consumerId)
            ->update([
                'status' => $status
            ]);
    }

    /**
     * | Get Consumer By consumerId
     * | @param ConsumerId
     */
    public function getConsumerById($consumerId)
    {
        WaterConsumer::where('id', $consumerId)
            ->firstOrFail();
    }


    /**
     * | Get the consumer details 
     * | Rearrangement of the old function coded above
     */
    public function getRefDetailByConsumerNo($key, $refNo)
    {
        return WaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'ulb_masters.logo',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicant_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->join('ulb_masters', 'ulb_masters.id', 'water_consumers.ulb_id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->where('water_consumers.' . $key, $refNo)
            ->where('water_consumers.status', 1)
            ->where('ulb_ward_masters.status', true)
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumers.id',
                'water_consumers.ulb_id',
                'ulb_masters.id',
                'water_consumers.ulb_id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.ward_mstr_id',
                'ulb_ward_masters.ward_name',
                'ulb_masters.logo',
            );
    }

    /**
     * | Fing data according to consumer No 
     * | @param consumerNo
     */
    public function getConsumerByNo($consumerNo)
    {
        return WaterConsumer::where('consumer_no', $consumerNo)
            ->where('status', 1)
            ->first();
    }

    /** 
     * | Get consumer by consumer id
     */
    public function getConsumerByIds($consumerIds)
    {
        return WaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicant_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->whereIn("water_consumers.id", $consumerIds)
            ->where('water_consumers.status', 1)
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumers.id',
                'water_consumers.ulb_id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.ward_mstr_id',
                'ulb_ward_masters.ward_name'
            );
    }

    /**
     * | Get water consumer according to apply connection id 
     */
    public function getConsumerByAppId($applicationId)
    {
        return WaterConsumer::where('apply_connection_id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * | Get consumers according to cosumer Ids
     */
    public function getConsumerListByIds($consumerIds)
    {
        return WaterConsumer::whereIn('id', $consumerIds)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}

<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WaterSecondConsumer extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get consumer by consumer Id
     */
    public function getConsumerDetailsById($consumerId)
    {
        return WaterSecondConsumer::where('id', $consumerId);
    }

    /**
     * apply for akola 
     */
    public function saveConsumer($req, $meta, $applicationNo)
    {
        $waterSecondConsumer = new WaterSecondConsumer();
        $waterSecondConsumer->ulb_id                    = $req->ulbId;
        $waterSecondConsumer->zone                      = $req->zone;
        $waterSecondConsumer->cycle                     = $req->Cycle;
        $waterSecondConsumer->property_no               = $req->PropertyNo;
        $waterSecondConsumer->consumer_no               = $applicationNo;
        $waterSecondConsumer->mobile_no                 = $req->MoblieNo;
        $waterSecondConsumer->address                   = $req->Address;
        $waterSecondConsumer->landmark                  = $req->PoleLandmark;
        $waterSecondConsumer->dtc_code                  = $req->DtcCode;
        $waterSecondConsumer->meter_make                = $req->MeterMake;
        $waterSecondConsumer->meter_no                  = $req->MeterNo;
        $waterSecondConsumer->meter_digit               = $req->MeterDigit;
        $waterSecondConsumer->tab_size                  = $req->TabSize;
        $waterSecondConsumer->meter_state               = $req->MeterState;
        $waterSecondConsumer->reading_date              = $req->ReadingDate;
        $waterSecondConsumer->connection_date           = $req->ConectionDate;
        $waterSecondConsumer->disconnection_date        = $req->DisconnectionDate;
        $waterSecondConsumer->disconned_reading         = $req->DisconnedDate;
        $waterSecondConsumer->book_no                   = $req->BookNo;
        $waterSecondConsumer->folio_no                  = $req->FolioNo;
        $waterSecondConsumer->no_of_connection          = $req->NoOfConnection;
        $waterSecondConsumer->is_meter_rented           = $req->IsMeterRented;
        $waterSecondConsumer->rent_amount               = $req->RentAmount;
        $waterSecondConsumer->total_installment         = $req->TotalInstallment;
        $waterSecondConsumer->nearest_consumer_no       = $req->NearestConsumerNo;
        $waterSecondConsumer->status                    = $meta['status'];
        $waterSecondConsumer->ward_mstr_id              = $meta['wardmstrId'];
        $waterSecondConsumer->category                  = $req->Category;
        $waterSecondConsumer->property_type_id          = $req->PropertyType;
        $waterSecondConsumer->meter_reading             = $req->MeterReading;
        $waterSecondConsumer->is_meter_working          = $req->IsMeterWorking;
        $waterSecondConsumer->connection_type_id        = $meta['connectionType'];


        $waterSecondConsumer->save();
        return $waterSecondConsumer;
    }

    /**
     * get all details 
     */

    public function getallDetails($applicationId)
    {
        return  WaterSecondConsumer::select(
            'water_second_consumers.*'

        )
            ->where('water_second_consumers.id', $applicationId)
            ->get();
    }

    /**
     * | Get active request by request id 
     */
    public function getActiveReqById($id)
    {
        return WaterSecondConsumer::where('id', $id)
            ->where('status', 4);
    }

    /**
     * | get the water consumer detaials by consumr No
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getDetailByConsumerNo($req, $key, $refNo)
    {
        return WaterSecondConsumer::select(
            'water_second_consumers.id',
            'water_second_consumers.consumer_no',
            'water_second_consumers.ward_mstr_id',
            'water_second_consumers.address',
            'water_second_consumers.ulb_id',
            "water_consumer_owners.applicant_name as owner_name",
            "water_consumer_owners.mobile_no",
            "water_consumer_owners.guardian_name"
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', 'water_second_consumers.id')
            ->where('water_second_consumers.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('water_second_consumers.status', 1);
    }
    /**
     * | get the water consumer detaials by Owner details
     * | @param consumerNo
     * | @var 
     * | @return 
     */
    public function getDetailByOwnerDetails($key, $refVal)
    {
        return WaterSecondConsumer::select(
            'water_second_consumers.id',
            'water_second_consumers.consumer_no',
            'water_second_consumers.ward_mstr_id',
            'water_second_consumers.address',
            'water_second_consumers.holding_no',
            'water_second_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            'water_consumer_owners.applicant_name as applicant_name',
            'water_consumer_owners.mobile_no as mobile_no',
            'water_consumer_owners.guardian_name as guardian_name',
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_second_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_second_consumers.ward_mstr_id')
            ->where('water_consumer_owners.' . $key, 'ILIKE', '%' . $refVal . '%')
            ->where('water_second_consumers.status', 1)
            ->where('ulb_ward_masters.status', true);
    }
    /**
     * get meter details of consumer
     */
    public function getDetailByMeterNo($key, $refNo)
    {
        return WaterSecondConsumer::select(
            'water_second_consumers.id',
            'water_second_consumers.consumer_no',
            'water_second_consumers.ward_mstr_id',
            'water_second_consumers.address',
            'water_second_consumers.ulb_id',
            "water_consumer_owners.applicant_name as owner_name",
            "water_consumer_owners.mobile_no",
            "water_consumer_owners.guardian_name",
            "water_consumer_meters.meter_no"

        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_second_consumers.id')
            ->join('water_consumer_meters', 'water_consumer_meters.consumer_id', 'water_second_consumers.id')
            ->where('water_consumer_meters.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('water_second_consumers.status', 1);
    }
    /**
     * | Update the payment status and the current role for payment 
     * | After the payment is done the data are update in active table
     */
    public function updateDataForPayment($applicationId, $req)
    {
        WaterSecondConsumer::where('id', $applicationId)
            ->where('status', 4)
            ->update($req);
    }

    /**
     * |----------------------- Get Water Application detals With all Relation ------------------|
     * | @param request
     * | @return 
     */
    public function fullWaterDetails($applicationId)
    {
        return WaterSecondConsumer::select(
            'water_second_consumers.*',
            'water_second_consumers.id',
            'water_second_consumers.consumer_no',
            // 'water_second_connection_charges.amount',
            // 'water_second_connection_charges.charge_category',
            'water_consumer_meters.meter_no',
            'water_consumer_meters.connection_type',
            'water_consumer_meters.initial_reading',
            'water_consumer_meters.final_meter_reading',
            'ulb_masters.ulb_name',
            "water_consumer_owners.applicant_name",
            "water_consumer_owners.guardian_name",
            'water_consumer_owners.mobile_no',
            "water_consumer_owners.email",
            "ulb_masters.association_with",
            DB::raw('ulb_ward_masters.ward_name as ward_number') // Alias the column as "ward_number"
        )
            ->join("water_consumer_owners", 'water_consumer_owners.consumer_id', 'water_second_consumers.id')
            ->join('ulb_masters', 'ulb_masters.id', 'water_second_consumers.ulb_id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'water_second_consumers.ward_mstr_id')
            ->join('water_consumer_meters', 'water_consumer_meters.consumer_id', 'water_second_consumers.id')
            // ->leftjoin('water_second_connection_charges', 'water_second_connection_charges.consumer_id', 'water_second_consumers.id')
            ->where('water_second_consumers.id', $applicationId)
            ->where('water_second_consumers.status', 1);
    }
    /**
     * | Get consumer 
     */
    public function getConsumerDetails($applicationId)
    {
        return WaterSecondConsumer::where('id', $applicationId)
            ->where('status', 1);
    }
    /**
     * 
     */
    public function fullWaterDetail($applicationId)
    {
        return WaterSecondConsumer::select(
            'water_second_consumers.*',
            'water_second_consumers.consumer_no',
            'water_second_connection_charges.amount',
            'water_second_connection_charges.charge_category',
            'water_consumer_meters.meter_no',
            'water_consumer_meters.connection_type',
            'water_consumer_meters.initial_reading',
            'water_consumer_meters.final_meter_reading',
            'ulb_masters.ulb_name',
            "water_consumer_owners.applicant_name",
            "water_consumer_owners.guardian_name",
            "water_consumer_owners.email",
            DB::raw('ulb_ward_masters.ward_name as ward_number')   // Alias the column as "ward_number"
        )
            ->join("water_consumer_owners", 'water_consumer_owners.consumer_id', 'water_second_consumers.id')
            ->join('ulb_masters', 'ulb_masters.id', 'water_second_consumers.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_second_consumers.ward_mstr_id')
            ->join('water_consumer_meters', 'water_consumer_meters.consumer_id', 'water_second_consumers.id')
            ->join('water_second_connection_charges', 'water_second_connection_charges.consumer_id', 'water_second_consumers.id')
            ->where('water_second_consumers.id', $applicationId)
            ->where('water_second_consumers.status', 4);
    }

    /**
     * | Get consumer Details By ConsumerId
     * | @param conasumerId
     */
    public function getConsumerDetailById($consumerId)
    {
        return WaterSecondConsumer::where('id', $consumerId)
            ->where('status', 1)
            ->firstOrFail();
    }
    /**
     * | Get water consumer according to apply connection id 
     */
    public function getConsumerByAppId($applicationId)
    {
        return WaterSecondConsumer::where('apply_connection_id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
    }
    /**
     * | Save the approved application to water Consumer
     * | @param consumerDetails
     * | @return
     */
    public function saveWaterConsumer($consumerDetails, $consumerNo)
    {
        $mWaterConsumer = new WaterSecondConsumer();
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
        $mWaterConsumer->rent_amount                 = $consumerDetails['rent_amount'] ?? null;
        $mWaterConsumer->approve_date                = Carbon::now();
        $mWaterConsumer->save();
        return $mWaterConsumer->id;
    }
}

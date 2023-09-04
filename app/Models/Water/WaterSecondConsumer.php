<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

            // 'ulb_ward_masters.ward_name',

        )
            // ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', 'water_second_consumers.ward_mstr_id')
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', 'water_second_consumers.id')
            ->where('water_second_consumers.' . $key, 'LIKE', '%' . $refNo . '%')
            // ->where('water_second_consumers.status', 1)
            ->groupBy(
                // "water_consumer_owners.applicant_name as owner_name",
                // "water_consumer_owners.mobile_no",
                // "water_consumer_owners.guardian_name",
                // 'water_second_consumers.id',
                // 'water_second_consumers.ulb_id',
                // 'water_second_consumers.consumer_no',
                // 'water_second_consumers.ward_mstr_id',
                // 'water_second_consumers.address',
                // 'ulb_ward_masters.ward_name'
            );
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
            DB::raw('ulb_ward_masters.ward_name as ward_number') // Alias the column as "ward_number"
        )
            ->join("water_consumer_owners", 'water_consumer_owners.consumer_id', 'water_second_consumers.id')
            ->join('ulb_masters', 'ulb_masters.id', 'water_second_consumers.ulb_id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'water_second_consumers.ward_mstr_id')
            ->join('water_consumer_meters', 'water_consumer_meters.consumer_id', 'water_second_consumers.id')
            ->leftjoin('water_second_connection_charges', 'water_second_connection_charges.consumer_id', 'water_second_consumers.id')
            ->where('water_second_consumers.id', $applicationId)
            ->where('water_second_consumers.status', 1);
    }
    /**
     * | Get consumer 
     */
    public function getConsumerDetails($applicationId)
    {
        return WaterSecondConsumer::where('id', $applicationId)
            ->where('status', 1)
            ->first();
    }
}

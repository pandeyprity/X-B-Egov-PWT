<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterSecondConsumer extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get consumer by consumer Id
     */
    public function getConsumerDetailsById($consumerId)
    {
        return WaterSecondConsumer::where('id',$consumerId);
    }

    /**
     * apply for akola 
     */
    public function saveConsumer($req,$meta,$applicationNo){
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
        $waterSecondConsumer->building_type             = $req->BuildingType;
        $waterSecondConsumer->no_of_connection          = $req->NoOfConnection;
        $waterSecondConsumer->is_meter_rented           = $req->IsMeterRented;
        $waterSecondConsumer->rent_amount               = $req->RentAmount;
        $waterSecondConsumer->total_installment         = $req->TotalInstallment;
        $waterSecondConsumer->nearest_consumer_no       = $req->NearestConsumerNo;
        $waterSecondConsumer->status                    = $meta['status'];

        $waterSecondConsumer->save();
        
 }

}

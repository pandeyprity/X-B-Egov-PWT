<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BdSettler extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Meta Request for store Settler records
     */
    public function metaReqs($req)
    {
        $abs = [
            'settler_name' => $req->settlerName,
            'mobile_no' => $req->mobileNo,
            'pan_no' => $req->panNo,
            'gst_no' => $req->gstNo,
            'settlement_from' => $req->settlementFrom,
            'settlement_upto' => $req->settlementUpto,
            'base_amount' => $req->baseAmount,
            'ulb_id' => $req->ulbId,
            'stand_category_id' => $req->standCategoryId,
            'stand_id' => $req->standId,
            'parking_id' => $req->parkingId,
            'bazar_id' => $req->bazarId,
            'banquet_hall_id' => $req->banquetHallId,
            'gst_amount' => $req->gstAmt,
            'tcs_amount' => $req->tcsAmt,
            'total_amount' => $req->totalAmount,
            'emd_amount' => $req->emdAmount,
            'bandobastee_type' => $req->bandobasteeType,
            'remarks' => $req->remarks
        ];
        return $abs;
    }

    /**
     * | Store function for Settler Records
     */
    public function addNew($req)
    {
        $metaReqs = $this->metaReqs($req);
        return BdSettler::create($metaReqs);
    }

    /**
     * | Get Settler Recors of Stand
     */
    public function listSettler($ulbId)
    {
        return BdSettler::select(
            'bd_settlers.*',
            DB::raw('cast(settlement_from as date) as settlement_from'),
            DB::raw('cast(settlement_upto as date) as settlement_upto'),
            // DB::raw("'' as installment_amount"),
            // DB::raw("DATE_FORMAT(bd_settlers.settlement_upto, '%d-%M-%Y') as formatted_dob"),
            'bd_stands.stand_name',
            'bd_stand_categories.stand_category as stand_type'
        )
            ->leftjoin('bd_stands', 'bd_stands.id', '=', 'bd_settlers.stand_id')
            ->leftjoin('bd_stand_categories', 'bd_stand_categories.id', '=', 'bd_settlers.stand_category_id')
            ->where('bd_settlers.stand_id', '!=', NULL)
            ->where('bd_settlers.ulb_id', $ulbId)
            ->orderBy('id')
            ->get();
    }

    /**
     * | Get Settler Records for Parking
     */
    public function listParkingSettler($ulbId)
    {
        return BdSettler::select(
            'bd_settlers.*',
            'bd_parkings.parking_name',
            DB::raw('cast(settlement_from as date) as settlement_from'),
            DB::raw('cast(settlement_upto as date) as settlement_upto'),
        )
            ->leftjoin('bd_parkings', 'bd_parkings.id', '=', 'bd_settlers.parking_id')
            ->where('bd_settlers.parking_id', '!=', NULL)
            ->where('bd_settlers.ulb_id', $ulbId)
            ->orderBy('id')
            ->get();
    }

    /** 
     * | Get Settler records for Bazar
     */
    public function listBazarSettler($ulbId)
    {
        return BdSettler::select(
            'bd_settlers.*',
            'bd_bazars.bazar_name',
            DB::raw('cast(settlement_from as date) as settlement_from'),
            DB::raw('cast(settlement_upto as date) as settlement_upto'),
        )
            ->leftjoin('bd_bazars', 'bd_bazars.id', '=', 'bd_settlers.bazar_id')
            ->where('bd_settlers.bazar_id', '!=', NULL)
            ->where('bd_settlers.ulb_id', $ulbId)
            ->orderBy('id')
            ->get();
    }

    /**
     * | Get Settler Records for Banquet Hall
     */
    public function listBanquetHallSettler($ulbId)
    {
        return BdSettler::select(
            'bd_settlers.*',
            'bd_banquet_halls.banquet_hall_name',
        )
            ->leftjoin('bd_banquet_halls', 'bd_banquet_halls.id', '=', 'bd_settlers.banquet_hall_id')
            ->where('bd_settlers.banquet_hall_id', '!=', NULL)
            ->where('bd_settlers.ulb_id', $ulbId)
            ->orderBy('id')
            ->get();
    }
}

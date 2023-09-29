<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ulbRevenueTargete extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];
    protected $connection = 'pgsql_master';

    public function insertData(array $data)
    {
        $reqs = [
            'ulb_id'            => $data["ulbId"],
            'amt_prop_trg'      => $data["amtPropTrg"]??0,
            'amt_prop_arr_trg'  => $data["amtPropArrTrg"]??0,
            'amt_prop_curr_trg' => $data["amtPropCurrTrg"]??0,

            'amt_water_trg'     => $data["amtWaterTrg"]??0,
            'amt_water_arr_trg' => $data["amtWaterArrTrg"]??0,
            'amt_water_curr_trg' => $data["amtWaterCurrTrg"]??0,

            'amt_trade_trg'     => $data["amtTradeTrg"]??0,
            'amt_trade_arr_trg' => $data["amtTradeArrTrg"]??0,
            'amt_trade_curr_trg' => $data["amtTradeCurrTrg"]??0,

            'user_id'           => $data["userId"]??null,
            'doc_refno'         => $data["docRefno"]??NULL,
            'doc_uniqueid'      => $data["docUniqueid"]??NULL,
            'doc_url'           => $data["docUrl"]??NULL,
            'effected_from'     => $data["effectedFrom"]??Carbon::now()->format('Y-m-d'),
        ];
        if(!isset($data["effectedUpto"]))
        {
            $fyear = calculateFYear($reqs['effected_from']);
            $reqs["effected_upto"] = explode('-',$fyear)[1]."03-31";
        }
        else{
            $reqs["effected_upto"] = $data["effectedUpto"];
        }
        ulbRevenueTargete::create($reqs)->id;
    }
}

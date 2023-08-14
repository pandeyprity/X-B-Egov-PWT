<?php

namespace App\Models\Markets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarketPriceMstr extends Model
{
    use HasFactory;

    /**
     * | Get Finisher Id while approve or reject application
     * | @param wfWorkflowId ulb workflow id 
     */
    public function getMarketTaxPrice($WfMasterId, $floor_area, $ulbId)
    {
        // DB::enableQueryLog();
        // $floor_area=5000;
        $price = MarketPriceMstr::select('market_price_mstrs.price', "ulb_masters.category")
            ->join(
                DB::raw("(SELECT *, category::int AS category_code 
                                FROM ulb_masters 
                                WHERE  id=$ulbId
                                ) AS ulb_masters"),
                function ($join) use ($ulbId) {
                    $join->on("market_price_mstrs.ulb_type", "ulb_masters.category_code")
                        ->where("ulb_masters.id", $ulbId);
                }
            )
            ->where('wf_master_id', $WfMasterId)
            // ->where('ulb_type', DB::raw("SELECT category FROM ulb_masters WHERE id=$ulbId"))
            ->where(
                function ($where) use ($floor_area) {
                    $where->where("range_from_sqft", "<=", ceil($floor_area))
                        ->where("range_upto_sqft", ">=", ceil($floor_area));
                }
            )
            ->first()->price;
        // dd(DB::getQueryLog());
        return $price;
    }

    /**
     * | Get price for hostel approved by state or indian government
     */
    public function getMarketTaxPriceGovtHostel($WfMasterId, $ulbId)
    {
        $price = MarketPriceMstr::select('market_price_mstrs.price', "ulb_masters.category")
            ->join(
                DB::raw("(SELECT *, category::int AS category_code 
                        FROM ulb_masters 
                        WHERE  id=$ulbId
                        ) AS ulb_masters"),
                function ($join) use ($ulbId) {
                    $join->on("market_price_mstrs.ulb_type", "ulb_masters.category_code")
                        ->where("ulb_masters.id", $ulbId);
                }
            )
            ->where('wf_master_id', $WfMasterId)
            ->where('is_governmental', '1')
            ->first()->price;
        return $price;
    }
}

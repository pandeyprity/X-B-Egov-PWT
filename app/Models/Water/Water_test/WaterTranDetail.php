<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTranDetail extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * | get Transaction Detail By transId
     * | @param tranId
     */
    public function getDetailByTranId($tranId)
    {
        return WaterTranDetail::where('tran_id', $tranId)
            ->where('status', true)
            ->firstorFail();
    }

    /**
     * | Save transaction details 
     */
    public function saveDefaultTrans($totalConnectionCharges, $applicationId, $transactionId, $connectionId)
    {
        $TradeDtl = new WaterTranDetail;
        $TradeDtl->tran_id          = $transactionId;
        $TradeDtl->demand_id        = $connectionId;
        $TradeDtl->total_demand     = $totalConnectionCharges;
        $TradeDtl->application_id   = $applicationId;
        $TradeDtl->created_at       = Carbon::now();
        $TradeDtl->save();
    }

    /**
     * | Get demand Ids by array of trans Ids
     * | @param transIds
     */
    public function getTransDemandByIds($transIds)
    {
        return WaterTranDetail::where('tran_id', $transIds)
            ->where('status', 1);
    }


    /**
     * | Save the transaction details for a transaction
     * | @param waterTrans
     * | @param charges
     * | @param applicationId
     * | @param amount
        | Not used 
     */
    public function saveTransactionDetails($waterTrans, $charges, $applicationId, $amount)
    {
        $waterTranDetail = new WaterTranDetail();
        $waterTranDetail->tran_id           = $waterTrans;
        $waterTranDetail->demand_id         = $charges;
        $waterTranDetail->application_id    = $applicationId;
        $waterTranDetail->total_demand      = $amount;
        $waterTranDetail->save();
    }
}

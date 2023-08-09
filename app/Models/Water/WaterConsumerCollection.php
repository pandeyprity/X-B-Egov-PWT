<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerCollection extends Model
{
    use HasFactory;

    /**
     * | Save consumer demand details for the transactions
     * | @param 
     */
    public function saveConsumerCollection($charges, $waterTrans, $refUserId)
    {
        $mWaterConsumerCollection = new WaterConsumerCollection();
        $mWaterConsumerCollection->consumer_id          = $charges->consumer_id;
        $mWaterConsumerCollection->ward_mstr_id         = $charges->ward_id;
        $mWaterConsumerCollection->transaction_id       = $waterTrans['id'];
        $mWaterConsumerCollection->amount               = $charges->amount;
        $mWaterConsumerCollection->emp_details_id       = $refUserId;
        $mWaterConsumerCollection->demand_id            = $charges->id;
        $mWaterConsumerCollection->demand_from          = $charges->demand_from;
        $mWaterConsumerCollection->demand_upto          = $charges->demand_upto;
        $mWaterConsumerCollection->penalty              = $charges->penalty;
        $mWaterConsumerCollection->payment_from         = null;
        $mWaterConsumerCollection->demand_payment_from  = null;
        $mWaterConsumerCollection->connection_type      = $charges->connection_type;
        $mWaterConsumerCollection->save();
    }
}

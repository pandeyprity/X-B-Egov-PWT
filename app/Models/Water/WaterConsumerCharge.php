<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerCharge extends Model
{
    use HasFactory;

    /**
     * | Get consumer charges using consumer Id
     */
    public function getConsumerCharges($consumerId)
    {
        return WaterConsumerCharge::where('consumer_id', $consumerId)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Save Consumer chsrges for the penalty
     */
    public function saveConsumerCharges($consumerCharges, $consumerId, $refChrgesCatagory)
    {
        $mWaterConsumerCharge = new WaterConsumerCharge();
        $mWaterConsumerCharge->consumer_id          = $consumerId;
        $mWaterConsumerCharge->charge_category      = $refChrgesCatagory;
        $mWaterConsumerCharge->charge_amount        = $consumerCharges['chargeAmount'];
        $mWaterConsumerCharge->penalty              = $consumerCharges['penalty'] ?? 0;
        $mWaterConsumerCharge->amount               = $consumerCharges['amount'];
        $mWaterConsumerCharge->rule_set             = $consumerCharges['ruleSet'];
        $mWaterConsumerCharge->charge_category_id   = $consumerCharges['chargeCategoryId'];
        $mWaterConsumerCharge->related_id           = $consumerCharges['relatedId'];
        $mWaterConsumerCharge->save();
        return [
            "id" => $mWaterConsumerCharge->id
        ];
    }
}

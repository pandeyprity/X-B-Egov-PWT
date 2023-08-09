<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropSafsDemand;
use App\Traits\Property\SAF;

class InsertTax
{
    use SAF;

    /**
     * | Save Generated Demand Tax
     * | @param safId
     * | @param userId 
     * | @param safTaxes
     */
    public function insertTax($safId, $ulbId, $safTaxes, $userId)
    {
        $details = $safTaxes;
        $safDemand = new PropSafsDemand();
        foreach ($details as $detail) {
            $reqs = [
                'saf_id' => $safId,
                'arv' => $detail['arv'],
                'water_tax' => $detail['waterTax'],
                'education_cess' => $detail['educationTax'],
                'health_cess' => $detail['healthCess'],
                'latrine_tax' => $detail['latrineTax'],
                'additional_tax' => $detail['rwhPenalty'],
                'holding_tax' => $detail['holdingTax'],
                'amount' => $detail['totalTax'],
                'fyear' => $detail['quarterYear'],
                'qtr' => $detail['qtr'],
                'due_date' => $detail['dueDate'],
                'user_id' => $userId,
                'ulb_id' => $ulbId,
                'adjust_amount' => $detail['adjustAmount'],
                'balance' => $detail['balance']
            ];
            $safDemand->postDemands($reqs);
        }
    }
}

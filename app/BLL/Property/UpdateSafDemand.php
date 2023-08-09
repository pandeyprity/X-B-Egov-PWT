<?php

namespace App\BLL\Property;

use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTranDtl;

/**
 * | For the updation of Saf Generated Demand
 */

class UpdateSafDemand
{
    private $_mPropSafDemand;
    private $_mPropTranDtl;

    public function __construct()
    {
        $this->_mPropSafDemand = new PropSafsDemand();
        $this->_mPropTranDtl = new PropTranDtl();
    }

    /**
     * | Update Demand
     */
    public function updateDemand(array $demands, $tranId): void
    {
        foreach ($demands as $demand) {
            $propDemand = $this->_mPropSafDemand::findOrFail($demand['id']);
            $propDemand->update([
                'balance' => 0,
                'paid_status' => 1
            ]);

            // Insert Prop Trans Demand Table
            $tranReq = [
                'tran_id' => $tranId,
                'saf_demand_id' => $demand['id'],
                'total_demand' => $demand['amount'],
                'ulb_id' => $demand['ulb_id'],
            ];
            $this->_mPropTranDtl->store($tranReq);
        }
    }
}

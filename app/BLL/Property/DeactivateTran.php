<?php

namespace App\BLL\Property;

use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use Exception;

/**
 * | Created On - 28/09/2023 
 * | Author - Anshu Kumar
 * | Email-9939anshu@gmail.com
 * | Objective-Transaction Deactivation of Property and Saf
 * | Required @param TransactionID to Deactivate
 * | Code - Open
 */

class DeactivateTran
{
    public $_tranId;
    private $_mPropTranDtl;
    private $_transaction;
    private $_tranDtls;

    public function __construct($tranId)
    {
        $this->_tranId = $tranId;
        $this->_mPropTranDtl = new PropTranDtl();
    }


    /**
     * | Deactivate transation of property or saf(1)
     */
    public function deactivate()
    {
        $this->_transaction = PropTransaction::find($this->_tranId);

        if (collect($this->_transaction)->isEmpty())
            throw new Exception("Transaction not found");

        if ($this->_transaction->is_arrear_settled == false)
            $this->_tranDtls = $this->_mPropTranDtl->getTranDemandsByTranId($this->_transaction->id);


        $this->deactivatePropTrans();                       // 1.1

        $this->deactivateSafTrans();                        // (1.2)  🔴🔴 Yet to complete

        $this->_transaction->status = 0;                    // Deactivation
        $this->_transaction->save();
    }


    /**
     * | Deactive Property Transactions (1.1)
     */
    public function deactivatePropTrans()
    {
        if ($this->_transaction->tran_type == 'Property') {
            $propId = $this->_transaction->property_id;
            foreach ($this->_tranDtls as $tranDtl) {
                $demand = PropDemand::find($tranDtl->prop_demand_id);
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available for demand ID $tranDtl->prop_demand_id");
                $demand->paid_status = 0;
                $demand->balance = $demand->total_tax - $demand->adjust_amt;
                $demand->save();

                // Tran Dtl Deactivation
                $tranDtl = PropTranDtl::find($tranDtl->id);
                $tranDtl->status = 0;               // Deactivation of tran Details
                $tranDtl->save();
            }
        }

        $property = PropProperty::find($propId);
        if (collect($property)->isEmpty())
            throw new Exception("Property Not Available for this Property ID $propId");

        $property->balance = $this->_transaction->arrear_settled_amt;
        $property->save();
    }

    /**
     * | Deactive saf transactions (1.2)
     */
    public function deactivateSafTrans()
    {
        if ($this->_transaction->tran_type == 'Saf') {
        }
    }
}

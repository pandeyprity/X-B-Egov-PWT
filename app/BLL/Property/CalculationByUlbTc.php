<?php

namespace App\BLL\Property;

use App\BLL\Property\TcVerificationDemandAdjust;

/**
 * | Created On-19-04-2023 
 * | Created By-Anshu Kumar
 * | Created for the Busines Logic Layer for Calculating Property Tax with Ulb Tc Data
 */

class CalculationByUlbTc extends TcVerificationDemandAdjust
{
    /**
     * | Function to Calculate The Tax According to Ulb Verification(1)
     */
    public function calculateTax(array $req)
    {
        $this->_reqs = $req;
        $this->readCalculationParams();                 // (Function 1.1)
        return $this->calculateQuaterlyTax();
    }

    /**
     * | Read Calculation Parameters to Perform task in TcVerificationDemandAdjust Class
     */
    public function readCalculationParams()
    {
        $this->_activeSafDtls = $this->_reqs['activeSafDtls'];
        $this->_tcId = collect($this->_reqs['fieldVerificationDtls'])->first()->user_id;
    }
}

<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class waterAudit extends Model
{
    use HasFactory;

    /**
     * | Save the related details of the edited details of water
     * | Save the related id's
     * | @param refWaterApplications
     * | @param refWaterowner
     * | @param refConnectionCharges
     * | @param refPenaltyInstallment
        | Not used 
     */
    public function saveUpdatedDetailsId($refWaterApplications,$refWaterowner,$refConnectionCharges,$refPenaltyInstallment)
    {
        $mwaterAudit = new waterAudit();
        $mwaterAudit->application_id        = $refWaterApplications ; 
        $mwaterAudit->applicant_id          = $refWaterowner ; 
        $mwaterAudit->penalty_id            = $refPenaltyInstallment; 
        $mwaterAudit->connection_charges_id = $refConnectionCharges; 
        $mwaterAudit->save(); 
    }
}

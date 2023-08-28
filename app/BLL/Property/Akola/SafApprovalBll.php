<?php

namespace App\BLL\Property\Akola;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropSafVerification;
use Exception;
use Illuminate\Http\Request;

/**
 * | Created On-28-08-2023 
 * | Created by-Anshu Kumar
 * | Created for the Saf Approval 
 */

/**
 * =========== Target ===================
 * 1) Property Generation and Replication
 * 2) Approved Safs and floors Replication
 * 3) Fam Generation
 */
class SafApprovalBll
{
    private $_safId;
    private $_mPropActiveSaf;
    private $_mPropActiveSafOwner;
    private $_mPropActiveSafFloor;
    private $_activeSaf;
    private $_ownerDetails;
    private $_floorDetails;
    private $_toBeProperties;
    private $_replicatedPropId;
    private $_mPropSafVerifications;

    // Initializations
    public function __construct()
    {
        $this->_mPropActiveSaf = new PropActiveSaf();
        $this->_mPropActiveSafFloor = new PropActiveSafsFloor();
        $this->_mPropActiveSafOwner = new PropActiveSafsOwner();
        $this->_mPropSafVerifications = new PropSafVerification();
    }

    /**
     * | Process of approval
     * | @param safId
     */
    public function approvalProcess($safId)
    {
        $this->_safId = $safId;

        $this->readParams();                    // ()

        $this->generateHoldingNo();

        $this->replicateProp();                 // ()

        $this->replicateSaf();                  // ()

        $this->famGeneration();                 // ()
    }


    /**
     * | Read Parameters                            // ()
     */
    public function readParams()
    {
        $this->_activeSaf = $this->_mPropActiveSaf->getQuerySafById($this->_safId);
        $this->_ownerDetails = $this->_mPropActiveSafOwner->getQueSafOwnersBySafId($this->_safId);
        $this->_floorDetails = $this->_mPropActiveSafFloor->getQSafFloorsBySafId($this->_safId);
        $this->_toBeProperties = $this->_mPropActiveSaf->toBePropertyBySafId($this->_safId);
    }

    /**
     * | Holding No Generation
     */
    public function generateHoldingNo()
    {
    }

    /**
     * | Replication of property()
     */
    public function replicateProp()
    {
        // Self Assessed Saf Prop Properties and Floors
        $propProperties = $this->_toBeProperties->replicate();
        $propProperties->setTable('prop_properties');
        $propProperties->saf_id = $this->_activeSaf->id;
        $propProperties->new_holding_no = $this->_activeSaf->holding_no;
        $propProperties->save();

        // Update the Verified Saf 
        $this->_replicatedPropId = $propProperties->id;

        $verifiedPropDetails = $this->_mPropSafVerifications->getVerifications($this->_safId);
        if (collect($verifiedPropDetails)->isEmpty())
            throw new Exception("Ulb Verification Details not Found");

        // Prop Floors Replication
        foreach ($this->_floorDetails as $floorDetail) {
            $propFloor = $floorDetail->replicate();
            $propFloor->setTable('prop_floors');
            $propFloor->property_id = $propProperties->id;
            $propFloor->save();
        }

        // Prop Owners replication
        foreach ($this->_ownerDetails as $ownerDetail) {
            $approvedOwners = $ownerDetail->replicate();
            $approvedOwners->setTable('prop_owners');
            $approvedOwners->property_id = $propProperties->id;
            $approvedOwners->save();
        }
    }

    /**
     * | Replication of Saf ()
     */
    public function replicateSaf()
    {
        $approvedSaf = $this->_activeSaf->replicate();
        $approvedSaf->setTable('prop_safs');
        $approvedSaf->id = $this->_activeSaf->id;
        $approvedSaf->property_id = $this->_replicatedPropId;
        $approvedSaf->save();
        $this->_activeSaf->delete();

        // Saf Owners Replication
        foreach ($this->_ownerDetails as $ownerDetail) {
            $approvedOwner = $ownerDetail->replicate();
            $approvedOwner->setTable('prop_safs_owners');
            $approvedOwner->id = $ownerDetail->id;
            $approvedOwner->save();
            $ownerDetail->delete();
        }
        if ($this->_activeSaf->prop_type_mstr_id != 4) {               // Applicable Not for Vacant Land
            // Saf Floors Replication
            foreach ($this->_floorDetails as $floorDetail) {
                $approvedFloor = $floorDetail->replicate();
                $approvedFloor->setTable('prop_safs_floors');
                $approvedFloor->id = $floorDetail->id;
                $approvedFloor->save();
                $floorDetail->delete();
            }
        }
    }

    /**
     * | Generation of FAM(04)
     */
    public function famGeneration()
    {
    }
}

<?php

namespace App\BLL\Property\Akola;

use App\MicroServices\IdGenerator\HoldingNoGenerator;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropFloor;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
    private $_mPropSafVerificationDtls;
    private $_verifiedPropDetails;
    private $_verifiedFloors;
    private $_mPropFloors;

    // Initializations
    public function __construct()
    {
        $this->_mPropActiveSaf = new PropActiveSaf();
        $this->_mPropActiveSafFloor = new PropActiveSafsFloor();
        $this->_mPropActiveSafOwner = new PropActiveSafsOwner();
        $this->_mPropSafVerifications = new PropSafVerification();
        $this->_mPropSafVerificationDtls = new PropSafVerificationDtl();
        $this->_mPropFloors = new PropFloor();
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
        $this->_verifiedPropDetails = $this->_mPropSafVerifications->getVerifications($this->_safId);
        $this->_toBeProperties = $this->_mPropActiveSaf->toBePropertyBySafId($this->_safId);

        if (collect($this->_verifiedPropDetails)->isEmpty())
            throw new Exception("Ulb Verification Details not Found");

        $this->_verifiedFloors = $this->_mPropSafVerificationDtls->getVerificationDetails($this->_verifiedPropDetails[0]->id);
    }

    /**
     * | Holding No Generation
     */
    public function generateHoldingNo()
    {
        $holdingNoGenerator = new HoldingNoGenerator;
        $ptParamId = Config::get('PropertyConstaint.PT_PARAM_ID');
        $idGeneration = new PrefixIdGenerator($ptParamId, $this->_activeSaf->ulb_id);
        // Holding No Generation
        $holdingNo = $holdingNoGenerator->generateHoldingNo($this->_activeSaf);
        $ptNo = $idGeneration->generate();
        $this->_activeSaf->pt_no = $ptNo;                        // Generate New Property Tax No for All Conditions
        $this->_activeSaf->holding_no = $holdingNo;
        $this->_activeSaf->save();
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

        $this->_replicatedPropId = $propProperties->id;
        // ✅Replication of Verified Saf Details by Ulb TC
        $propProperties->prop_type_mstr_id = $this->_verifiedPropDetails[0]->prop_type_id;
        $propProperties->area_of_plot = $this->_verifiedPropDetails[0]->area_of_plot;
        $propProperties->ward_mstr_id = $this->_verifiedPropDetails[0]->ward_id;
        $propProperties->is_mobile_tower = $this->_verifiedPropDetails[0]->has_mobile_tower;
        $propProperties->tower_area = $this->_verifiedPropDetails[0]->tower_area;
        $propProperties->tower_installation_date = $this->_verifiedPropDetails[0]->tower_installation_date;
        $propProperties->is_hoarding_board = $this->_verifiedPropDetails[0]->has_hoarding;
        $propProperties->hoarding_area = $this->_verifiedPropDetails[0]->hoarding_area;
        $propProperties->hoarding_installation_date = $this->_verifiedPropDetails[0]->hoarding_installation_date;
        $propProperties->is_petrol_pump = $this->_verifiedPropDetails[0]->is_petrol_pump;
        $propProperties->under_ground_area = $this->_verifiedPropDetails[0]->underground_area;
        $propProperties->petrol_pump_completion_date = $this->_verifiedPropDetails[0]->petrol_pump_completion_date;
        $propProperties->is_water_harvesting = $this->_verifiedPropDetails[0]->has_water_harvesting;
        $propProperties->save();

        // ✅✅Verified Floors replication
        foreach ($this->_verifiedFloors as $floorDetail) {
            $floorReq = [
                "property_id" => $this->_replicatedPropId,
                "saf_id" => $this->_safId,
                "floor_mstr_id" => $floorDetail->floor_mstr_id,
                "usage_type_mstr_id" => $floorDetail->usage_type_id,
                "const_type_mstr_id" => $floorDetail->construction_type_id,
                "occupancy_type_mstr_id" => $floorDetail->occupancy_type_id,
                "builtup_area" => $floorDetail->builtup_area,
                "date_from" => $floorDetail->date_from,
                "date_upto" => $floorDetail->date_to,
                "carpet_area" => $floorDetail->carpet_area,
                "user_id" => $floorDetail->user_id,
                "saf_floor_id" => $floorDetail->saf_floor_id
            ];
            $this->_mPropFloors->create($floorReq);
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

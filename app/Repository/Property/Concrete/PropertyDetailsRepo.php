<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use Exception;

/**
 * | Created On-26-11-2022 
 * | Created By-Sam kerkettta
 * --------------------------------------------------------------------------------------
 * | Controller regarding with Propery Module (Property Details)
 */


class PropertyDetailsRepo implements iPropertyDetailsRepo
{
    /**
     * |--------------------------------------- filtring the details of Property / returning-----------------------------------------------
     * | @param request
     * | @param error
     * | @var requestDetails
     * | @var filterByHolding
     * | @var filterByOwner
     * | @var filterByAddress
     * |
     * | Rating : 2
     * | time :  
     * | Operation : function filters the property details according to holdingNo/ ownerdetails/ Adddress/ wardID/ 
     */
    public function getFilterProperty($request)
    {
        try {
            $requestDetails = $request->filteredBy;
            switch ($requestDetails) {
                case ("HoldingNo"):
                    $holding = $this->searchByHolding($request);
                    $filterByHolding = collect($holding)->first();
                    if (empty($filterByHolding)) {
                        return responseMsg(false, "Data Not Found!", $request->search);
                    }
                    return responseMsg(true, "Data According to Holding!", remove_null($filterByHolding));
                    break;
                case ("OwnerDetail"):
                    return $owner = $this->searchByOwner($request);
                    $filterByOwner = collect($owner)->first();
                    if (empty($filterByOwner)) {
                        return responseMsg(false, "Data Not Found!", $request->search);
                    }
                    return responseMsg(true, "Data According to Owner!", remove_null($filterByOwner));
                    break;
                case ("Address"):
                    $filterByAddress = $this->searchByAddress($request);
                    if (empty($filterByAddress['0'])) {
                        return responseMsg(false, "Data Not Found!", $request->search);
                    }
                    return responseMsg(true, "Data According to Address!", remove_null($filterByAddress));
                    break;
                default:
                    return responseMsg(false, "Not a Valid Entry for Filtration Error Retry!", "");
            }
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |-------------------------------- Called Function for the prop Details 1.1 -----------------------------------------------
     * | @param request
     * | Searching the details of the property according to holdingNo 
     */
    public function searchByHolding($request)
    {
        return PropProperty::select(
            'prop_properties.id AS id',
            'prop_properties.new_holding_no AS holdingNo',
            'prop_properties.prop_address AS address',
            'corr_address AS correspondingAddress',
            'owner_name AS ownerName',
            'mobile_no AS mobileNo'
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->where('new_holding_no', $request->search)
            ->get();
    }

    /**
     * |-------------------------------- Called Function for the prop Details 1.2 -----------------------------------------------
     * | @param request
     * | Searching the detais of the property according to owner details ie. owners Name/ owners Mobile name / Pan no / addhar card
     */
    public function searchByOwner($request)
    {
        if (($request->wardId) == 0 || is_null($request->wardId)) {
            return PropProperty::select(
                'prop_properties.id AS id',
                'prop_properties.new_holding_no AS holdingNo',
                'prop_properties.prop_address AS address',
                'corr_address AS correspondingAddress',
                'owner_name AS ownerName',
                'mobile_no AS mobileNo'
            )
                ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
                ->where('ulb_id', authUser($request)->ulb_id)
                ->where('owner_name', $request->search)
                ->orwhere('mobile_no', $request->search)
                ->orwhere('pan_no', $request->search)
                ->orwhere('aadhar_no', $request->search)
                ->get();
        }
        return PropProperty::select(
            'prop_properties.id AS id',
            'prop_properties.new_holding_no AS holdingNo',
            'prop_properties.prop_address AS address',
            'corr_address AS correspondingAddress',
            'owner_name AS ownerName',
            'mobile_no AS mobileNo'
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->where('ulb_id', authUser($request)->ulb_id)
            ->where('prop_properties.ward_mstr_id', $request->wardId)
            ->where('owner_name', $request->search)
            ->orwhere('mobile_no', $request->search)
            ->orwhere('pan_no', $request->search)
            ->orwhere('aadhar_no', $request->search)
            ->get();
    }

    /**
     * |-------------------------------- Called Function for the prop Details 1.2 -----------------------------------------------
     * | @param request
     * | searching the details of the owners according to addresss 
     */
    public function searchByAddress($request)
    {
        if (($request->wardId) == 0) {
            return PropProperty::select(
                'prop_properties.id AS id',
                'prop_properties.new_holding_no AS holdingNo',
                'prop_properties.prop_address AS address',
                'corr_address AS correspondingAddress',
                'owner_name AS ownerName',
                'mobile_no AS mobileNo'
            )
                ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
                ->where('prop_properties.prop_address', $request->search)
                ->get();
        }
        return PropProperty::select(
            'prop_properties.id AS id',
            'prop_properties.new_holding_no AS holdingNo',
            'prop_properties.prop_address AS address',
            'corr_address AS correspondingAddress',
            'owner_name AS ownerName',
            'mobile_no AS mobileNo'
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_properties.id')
            ->where('prop_properties.ward_mstr_id', $request->wardId)
            ->where('prop_properties.prop_address', $request->search)
            ->get();
    }




    /**
     * |-------------------------- filtring the details according to workflow / returning / 2 -----------------------------------------------
     * | @param request
     * | @var requestDetails
     * | @var waterHarvesting
     * | @var filterByConcession
     * | @var filterByObjestion
     * | @var filterByMutation
     * | @var filterByReAssisment
     * | @var filterByNewAssisment
     * |
     * | Rating : 2
     * | Time : 
     * | Operation : function filter the details of the the applicants according to workflow / only the applied Applications
     */
    public function getFilterSafs($request)
    {
        try {
            $requestDetails = $request->filteredBy;
            switch ($requestDetails) {
                case ("rainWaterHarvesting"): {
                        $waterHarvesting = new PropActiveHarvesting();  //<---------- May change
                        return $waterHarvesting->allRwhDetails($request);
                    }
                    break;
                case ("concession"): {
                        $filterByConcession = $this->searchByConcession($request);
                        if (empty($filterByConcession['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to Concession!", remove_null($filterByConcession));
                    }
                    break;
                case ("objection"): {
                        $filterByObjestion = $this->searchByObjection($request);
                        if (empty($filterByObjestion['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to Objection!", remove_null($filterByObjestion));
                    }
                    break;
                case ("mutation"): {
                        $filterByMutation = $this->searchByMutation($request);
                        if (empty($filterByMutation['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to Mutation!", remove_null($filterByMutation));
                    }
                    break;
                case ("reAssisment"): {
                        $filterByReAssisment = $this->searchByReAssisment($request);
                        if (empty($filterByReAssisment['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to ReAssisment!", remove_null($filterByReAssisment));
                    }
                    break;
                case ("newAssisment"): {
                        $filterByNewAssisment = $this->searchByNewAssisment($request);
                        if (empty($filterByNewAssisment['0'])) {
                            return responseMsg(false, "Data Not Found!", $request->search);
                        }
                        return responseMsg(true, "Data According to NewAssisment!", remove_null($filterByNewAssisment));
                    }
                    break;
                default:
                    return responseMsg(false, "Not a Valid Entry for Filtration Error Retry!", $request->filteredBy);
            }
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |-------------------------- details of Active Concillation  2.1 -----------------------------------------------
     * | @param request
     * | Operation : serch applicants by ward/ cncession Id
     */
    public function searchByConcession($request)
    {
        if (($request->wardId) == 0) {
            return  PropActiveConcession::select(
                'prop_active_concessions.id AS id',
                'prop_active_concessions.application_no AS applicationNo',
                'prop_active_concessions.applicant_name AS name',
                'prop_owners.mobile_no AS mobileNo',
            )
                ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_active_concessions.property_id')
                ->where('prop_active_concessions.application_no', $request->search) //<-----here
                ->get();
        }
        return  PropActiveConcession::select(
            'prop_active_concessions.id AS id',
            'prop_active_concessions.application_no AS applicationNo',
            'prop_active_concessions.applicant_name AS name',
            'prop_owners.mobile_no AS mobileNo',
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_active_concessions.property_id')
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_active_concessions.property_id')
            ->where('prop_properties.ward_mstr_id', $request->wardId)
            ->get();
    }

    /**
     * |-------------------------- details of Active Objection  2.2 -----------------------------------------------
     * | @param request
     * | Operation : serch Objection applicants by ward / objectionId
     */
    public function searchByObjection($request)
    {
        if (($request->wardId) == 0) {
            return PropActiveObjection::select(
                'prop_active_objections.id AS id',
                'prop_active_objections.objection_no AS applicationNo',
                'prop_active_objection_owners.name',
                'prop_active_objection_owners.mobile',
            )
                ->join('prop_active_objection_owners', 'prop_active_objection_owners.objection_id', '=', 'prop_active_objections.id')
                ->where('prop_active_objections.objection_no', $request->search)
                ->get();
        }
        return PropActiveObjection::select(
            'prop_active_objections.id AS id',
            'objection_no AS applicationNo',
            'prop_owners.owner_name AS name',
            'prop_owners.mobile_no AS mobileNo',
        )
            ->join('prop_owners', 'prop_owners.property_id', '=', 'prop_active_objections.property_id')
            ->join('prop_properties', 'prop_properties.id', '=', 'prop_active_objections.property_id')
            ->where('prop_properties.ward_mstr_id', $request->wardId)
            ->get();
    }

    /**
     * |-------------------------- details of Active Mutation 2.3 -----------------------------------------------
     * | @param request
     * | Opertion : serch mutation applicant by ward / mutationId
     */
    public function searchByMutation($request)
    {
        if (($request->wardId) == 0) {
            return PropActiveSaf::select(
                'prop_active_safs.id AS id',
                'saf_no AS applicationNo',
                'prop_active_safs_owners.owner_name AS name',
                'prop_active_safs_owners.mobile_no AS mobileNo'
            )
                ->join('prop_active_safs_owners', 'prop_active_safs_owners.saf_id', '=', 'prop_active_safs.id')
                ->where('prop_active_safs.saf_no', $request->search)
                ->where('prop_active_safs.property_assessment_id', 3)
                ->get();
        }
        return PropActiveSaf::select(
            'prop_active_safs.id AS id',
            'saf_no AS applicationNo',
            'prop_active_safs_owners.owner_name AS name',
            'prop_active_safs_owners.mobile_no AS mobileNo'
        )
            ->join('users', 'users.id', '=', 'prop_active_safs.user_id')
            ->where('prop_active_safs.ward_mstr_id', $request->wardId)
            ->where('property_assessment_id', 3)
            ->get();
    }

    /**
     * |-------------------------- details of Active Re Assisment 2.4-----------------------------------------------
     * | @param request
     * | Operation : serch applicants by ward / reassisment details
     */
    public function searchByReAssisment($request)
    {
        if (($request->wardId) == 0) {
            return PropActiveSaf::select(
                'prop_active_safs.id AS id',
                'saf_no AS applicationNo',
                'prop_active_safs_owners.owner_name AS name',
                'prop_active_safs_owners.mobile_no AS mobileNo'
            )
                ->join('prop_active_safs_owners', 'prop_active_safs_owners.saf_id', '=', 'prop_active_safs.id')
                ->where('prop_active_safs.saf_no', $request->search)
                ->where('prop_active_safs.property_assessment_id', 2)
                ->get();
        }
        return PropActiveSaf::select(
            'prop_active_safs.id AS id',
            'saf_no AS applicationNo',
            'prop_active_safs_owners.owner_name AS name',
            'prop_active_safs_owners.mobile_no AS mobileNo'
        )
            ->join('prop_active_safs_owners', 'prop_active_safs_owners.saf_id', '=', 'prop_active_safs.id')
            ->where('prop_active_safs.ward_mstr_id', $request->wardId)
            ->where('property_assessment_id', 2)
            ->get();
    }

    /**
     * |-------------------------- details of Active New Assisment 2.5-----------------------------------------------
     * | @param request
     * | Opration : serch applicants by ward / newAssisment details
     */
    public function searchByNewAssisment($request)
    {
        if (($request->wardId) == 0) {
            return PropActiveSaf::select(
                'prop_active_safs.id AS id',
                'saf_no AS applicationNo',
                'prop_active_safs_owners.owner_name AS name',
                'prop_active_safs_owners.mobile_no AS mobileNo'
            )
                ->join('prop_active_safs_owners', 'prop_active_safs_owners.saf_id', '=', 'prop_active_safs.id')
                ->where('prop_active_safs.saf_no', $request->search)
                ->where('prop_active_safs.property_assessment_id', 1)
                ->get();
        }
        return PropActiveSaf::select(
            'prop_active_safs.id AS id',
            'saf_no AS applicationNo',
            'prop_active_safs_owners.owner_name AS name',
            'prop_active_safs_owners.mobile_no AS mobileNo'
        )
            ->join('prop_active_safs_owners', 'prop_active_safs_owners.saf_id', '=', 'prop_active_safs.id')
            ->where('prop_active_safs.ward_mstr_id', $request->wardId)
            ->where('property_assessment_id', 1)
            ->get();
    }




    /**
     * |-------------------------- details of Active Applicatins Accoring to Id / 3 -----------------------------------------------
     * | @param request
     * | @param error
     * | @var requestDetails
     * | @var waterHarvesting 
     * | @var concession
     * | @var objection
     * | @var mutation
     * | @var reAssisment
     * | @var newAssisment
     * |
     * | Operation : filteration according to the request details ie. waterHarvesting/ concession/ objection/ mutation/ reAssisment/ newAssisment and there respective Id comming from request
     * | rating : 2
     * | timing : 275 ms
     */
    public function getUserDetails($request)
    {
        try {
            $requestDetails = $request->filterBy;
            switch ($requestDetails) {
                case ("rainWaterHarvesting"): {
                        $waterHarvesting = new PropActiveHarvesting();
                        $mWaterHarvesting = $waterHarvesting->allDetails($request);
                        if (empty($mWaterHarvesting['0'])) {
                            return responseMsg(false, "Data Not Found on Respective Id!", $request->id);
                        }
                        return responseMsg(true, "Data of RainWaterHarvesting!", remove_null($mWaterHarvesting));
                    }
                    break;
                case ("concession"): {
                        $concession = new PropActiveConcession();
                        $mConcession = $concession->allConcession($request);
                        if (empty($mConcession['0'])) {
                            return responseMsg(false, "Data Not Found on Respective Id!", $request->id);
                        }
                        return responseMsg(true, "Data of Concesion!", remove_null($mConcession));
                    }
                    break;
                case ("objection"): {
                        $objection = new PropActiveObjection();
                        $mObjection = $objection->allObjection($request);
                        if (empty($mObjection['0'])) {
                            return responseMsg(false, "Data Not Found on Respective Id!", $request->id);
                        }
                        return responseMsg(true, "Data of Objection!", remove_null($mObjection));
                    }
                    break;
                case ("mutation"): {
                        $mutation = new PropActiveSaf();
                        $mMutation = $mutation->allMutation($request);
                        if (empty($mMutation['0'])) {
                            return responseMsg(false, "Data Not Found on Respective Id!", $request->id);
                        }
                        return responseMsg(true, "Data of Mutation!", remove_null($mMutation));
                    }
                    break;
                case ("reAssisment"): {
                        $reAssisment = new PropActiveSaf();
                        $mReAssisment = $reAssisment->allReAssisment($request);
                        if (empty($mReAssisment['0'])) {
                            return responseMsg(false, "Data Not Found on Respective Id!", $request->id);
                        }
                        return responseMsg(true, "Data of ReAssisment!", remove_null($mReAssisment));
                    }
                    break;
                case ("newAssisment"): {
                        $newAssisment = new PropActiveSaf();
                        $mNewAssisment = $newAssisment->allNewAssisment($request);
                        if (empty($mNewAssisment['0'])) {
                            return responseMsg(false, "Data Not Found on Respective Id!", $request->id);
                        }
                        return responseMsg(true, "Data of NewAssisment!", remove_null($mNewAssisment));
                    }
                    break;
                default:
                    return responseMsg(false, "Not a Valid Entry for Filtration Error Retry!", $request->filterBy);
            }
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}

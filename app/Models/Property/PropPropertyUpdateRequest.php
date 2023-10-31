<?php

namespace App\Models\Property;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PropPropertyUpdateRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function store($req)
    {
        $reqs = [
            "prop_id"   =>  $req->propId,
            "holding_no" => $req->holdingNo,
            "new_holding_no" => $req->newHoldingNo,
            "pt_no"=>  $req->ptNo,
            "property_no"=>  $req->propertyNo,
            "logs" =>  $req->logs,
            "supportingDocument"=>$req->supportingDocument,
            "unique_id"=>$req->uniqueId,
            "reference_no" => $req->referenceNo,

            'has_previous_holding_no' => $req->hasPreviousHoldingNo,
            'previous_holding_id' => $req->previousHoldingId,
            'previous_ward_mstr_id' => $req->previousWard,
            'is_owner_changed' => $req->isOwnerChanged,
            'transfer_mode_mstr_id' => $req->transferModeId ?? null,
            'holding_no' => $req->holdingNo,
            'ward_mstr_id' => $req->ward,
            'ownership_type_mstr_id' => $req->ownershipType,
            'prop_type_mstr_id' => $req->propertyType,
            'appartment_name' => $req->appartmentName,
            'flat_registry_date' => $req->flatRegistryDate,
            'zone_mstr_id' => $req->zone,
            'no_electric_connection' => $req->electricityConnection,
            'elect_consumer_no' => $req->electricityCustNo,
            'elect_acc_no' => $req->electricityAccNo,
            'elect_bind_book_no' => $req->electricityBindBookNo,
            'elect_cons_category' => $req->electricityConsCategory,
            'building_plan_approval_no' => $req->buildingPlanApprovalNo,
            'building_plan_approval_date' => $req->buildingPlanApprovalDate,
            'water_conn_no' => $req->waterConnNo,
            'water_conn_date' => $req->waterConnDate,
            'khata_no' => $req->khataNo,
            'plot_no' => $req->plotNo,
            'village_mauja_name' => $req->villageMaujaName,
            'road_type_mstr_id' => $req->roadWidthType,
            'area_of_plot' => $req->areaOfPlot,
            'prop_address' => $req->propAddress,
            'prop_city' => $req->propCity,
            'prop_dist' => $req->propDist,
            'prop_pin_code' => $req->propPinCode,
            'is_corr_add_differ' => $req->isCorrAddDiffer,
            'corr_address' => $req->corrAddress,
            'corr_city' => $req->corrCity,
            'corr_dist' => $req->corrDist,
            'corr_pin_code' => $req->corrPinCode,
            'holding_type' => $req->holdingType,
            'is_mobile_tower' => $req->isMobileTower,
            'tower_area' => $req->mobileTower['area'] ?? null,
            'tower_installation_date' => $req->mobileTower['dateFrom'] ?? null,

            'is_hoarding_board' => $req->isHoardingBoard,
            'hoarding_area' => $req->hoardingBoard['area'] ?? null,
            'hoarding_installation_date' => $req->hoardingBoard['dateFrom'] ?? null,


            'is_petrol_pump' => $req->isPetrolPump,
            'under_ground_area' => $req->petrolPump['area'] ?? null,
            'petrol_pump_completion_date' => $req->petrolPump['dateFrom'] ?? null,

            'is_water_harvesting' => $req->isWaterHarvesting,
            'rwh_date_from' => ($req->isWaterHarvesting == 1) ? $req->rwhDateFrom : null,
            'land_occupation_date' => $req->landOccupationDate?$req->landOccupationDate:$req->dateOfPurchase,
            'doc_verify_cancel_remarks' => $req->docVerifyCancelRemark,
            'application_date' =>  $req->applicationDate,
            'assessment_type' => $req->assessmentType,
            'saf_distributed_dtl_id' => $req->safDistributedDtl,
            'prop_dtl_id' => $req->propDtl,
            'prop_state' => $req->propState,
            'corr_state' => $req->corrState,
            'holding_type' => $req->holdingType,
            'ip_address' => getClientIpAddress(),
            'new_ward_mstr_id' => $req->newWard,
            "entry_type"    =>  $req->entryType,
            'percentage_of_property_transfer' => $req->percOfPropertyTransfer,
            'apartment_details_id' => $req->apartmentId,
            'applicant_name' => $req->applicantName,
            'applicant_marathi' => $req->applicantMarathi,
            'road_width' => $req->roadType,
            'user_id' => $req->userId,
            'workflow_id' => $req->workflowId,
            'ulb_id' => $req->ulbId,
            'pending_status' => $req->pendingStatus??0,
            'current_role' => $req->initiatorRoleId,
            'initiator_role_id' => $req->initiatorRoleId,
            'finisher_role_id' => $req->finisherRoleId,
            'citizen_id' => $req->citizenId ?? null,

            'building_name' => $req->buildingName,
            'street_name' => $req->streetName,
            'location' => $req->location,
            'landmark' => $req->landmark,
            'is_gb_saf' => isset($req->isGBSaf) ? $req->isGBSaf : false,
            'gb_office_name' => isset($req->gbOfficeName) ? $req->gbOfficeName : null,
            'gb_usage_types' => isset($req->gbUsageTypes) ? $req->gbUsageTypes : null,
            'gb_prop_usage_types' => isset($req->gbPropUsageTypes) ? $req->gbPropUsageTypes : null,
            'is_trust' => $req->isTrust ?? false,
            'trust_type' => $req->trustType ?? null,
            'is_trust_verified' => $req->isTrustVerified ?? false,
            'category_id' => $req->category,
        ];
        return PropPropertyUpdateRequest::create($reqs)->id;   
    }
}

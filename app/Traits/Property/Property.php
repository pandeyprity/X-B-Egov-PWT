<?php

namespace App\Traits\Property;

use App\Models\Property\PropOwner;
use App\Models\Property\PropOwnerUpdateRequest;
use App\Models\Property\PropProperty;
use App\Models\Property\PropPropertyUpdateRequest;
use Exception;
use Illuminate\Http\Request;

/**
 * | Trait for Property and SAF reusable 
 * | Created On-08-12-2022 
 * | Created By-Anshu Kumar
 */

trait Property
{
    public function generatePropUpdateRequest(Request $request,PropProperty $property,bool $fullEdit=false)
    {
        $arr= [
            "propId"=>$property->id,
            "holdingNo" => $property->holding_no,
            "newHoldingNo" => $property->new_holding_no,
            "ptNo"=>  $property->pt_no,
            "propertyNo"=>  $property->property_no,
            "logs"=> json_encode($property->toArray(), JSON_UNESCAPED_UNICODE),
            "supportingDocument"=>$request->supportingDocument,
            "uniqueId"=>$request->uniqueId,
            "referenceNo" => $request->referenceNo,

            "ulbId"=> $request->UlbId ?? $property->ulb_id,
            "clusterId" => $request->clusterId ?? $property->cluster_id,
            "safId" => $request->safId ?? $property->saf_id,            
            "applicantName" => $request->applicantName ??  $property->applicant_name,
            "applicantMarathi" => $request->applicantMarathi ??  $property->applicant_marathi,
            "applicationDate" => $request->applicationDate ??  $property->application_date,
            "ward" => $request->ward ??  $property->ward_mstr_id,
            "ownershipType" => $request->ownershipType ??  $property->ownership_type_mstr_id,
            "electricityConnection" => $request->electricityConnection ??  $property->no_electric_connection,
            "electricityCustNo" => $request->electricityCustNo ??  $property->elect_consumer_no,
            "electricityAccNo" => $request->electricityAccNo ??  $property->elect_acc_no,
            "electricityBindBookNo" => $request->electricityBindBookNo ??  $property->elect_bind_book_no,
            "electricityConsCategory" => $request->electricityConsCategory ??  $property->elect_cons_category,            
            "waterConnNo" => $request->waterConnNo ??  $property->water_conn_no,  
            "waterConnDate" => $request->waterConnDate ??  $property->water_conn_date, 
            "khataNo" => $request->khataNo ??  $property->khata_no,
            "plotNo" => $request->plotNo ??  $property->plot_no,
            "villageMaujaName" => $request->villageMaujaName ??  $property->village_mauja_name,   
            "propAddress" => $request->propAddress ??  $property->prop_address, 
            "propCity" => $request->propCity ??  $property->prop_city,    
            "propDist" => $request->propDist ??  $property->prop_dist,  
            "propPinCode" => $request->propPinCode ??  $property->prop_pin_code,  
            "propState" => $request->propState ??  $property->prop_state,  
            "corrAddress" => $request->corrAddress ??  $property->corr_address, 
            "corrCity" => $request->corrCity ??  $property->corr_city,
            "corrDist" => $request->corrDist ??  $property->corr_dist,
            "corrPinCode" => $request->corrPinCode ??  $property->corr_pin_code,
            "corrState" => $request->corrState ??  $property->corr_state, 
            "newWard" => $request->newWard ??  $property->new_ward_mstr_id,   
            "entryType" => $request->entryType ??  $property->entry_type,  
            "zone" => $request->zone ??  $property->zone_mstr_id,  
            "buildingName" => $request->buildingName ??  $property->building_name, 
            "streetName" => $request->streetName ??  $property->street_name, 
            "location" => $request->location ??  $property->location, 
            "landmark" => $request->landmark ??  $property->landmark,
        ];
        $arr2 = [];
        if($fullEdit)
        {
            $arr2=[
            "appartmentName" => $request->appartmentName ?? $property->appartment_name,
            "propertyType" =>  $request->propertyType ?? $property->prop_type_mstr_id,
            "buildingPlanApprovalNo" =>  $request->buildingPlanApprovalNo ??  $property->building_plan_approval_no,
            "buildingPlanApprovalDate" =>  $request->buildingPlanApprovalDate ??  $property->building_plan_approval_date,
            "roadWidthType" =>   $request->roadWidthType ??  $property->road_type_mstr_id,
            "roadType" =>  $request->roadType ??  $property->road_width,
            "areaOfPlot" =>   $request->areaOfPlot ??  $property->area_of_plot,
            "isMobileTower" =>  $request->isMobileTower ??  $property->is_mobile_tower,
            "mobileTower" => ["area"=> $request->mobileTower['area'] ??  $property->tower_area,
                              "dateFrom"=> $request->mobileTower['dateFrom'] ??  $property->tower_installation_date
                            ],

            "isHoardingBoard" =>  $request->isHoardingBoard ??  $property->is_hoarding_board,
            "hoardingBoard" => ["area"=> $request->hoardingBoard['area'] ??  $property->hoarding_area,
                                "dateFrom"=> $request->hoardingBoard['dateFrom'] ??  $property->hoarding_installation_date
                               ],
            
            "isPetrolPump" =>  $request->isPetrolPump ??  $property->is_petrol_pump,
            "petrolPump" => ["area"=> $request->petrolPump['area'] ??  $property->under_ground_area,
                            "dateFrom"=> $request->petrolPump['dateFrom'] ??  $property->petrol_pump_completion_date
                            ],

            "isWaterHarvesting" =>  $request->isWaterHarvesting ??  $property->is_water_harvesting,            
            "rwhDateFrom" =>  $request->rwhDateFrom ??  $property->rwh_date_from,

            "dateOfPurchase" =>  $request->landOccupationDate ??  $property->land_occupation_date,
            "flatRegistryDate" =>  $request->flatRegistryDate ??  $property->flat_registry_date,
            "assessmentType" =>  $request->assessmentType ??  $property->assessment_type,
            "holdingType" =>  $request->holdingType ??  $property->holding_type,
            "apartmentId" =>  $request->apartmentId ??  $property->apartment_details_id,
            "isGBSaf" =>  $request->isGBSaf ??  $property->is_gb_saf,
            "gbOfficeName" =>  $request->gbOfficeName ??  $property->gb_office_name,
            "gbUsageTypes" =>  $request->gbUsageTypes ??  $property->gb_usage_types,
            "gbPropUsageTypes" =>  $request->gbPropUsageTypes ??  $property->gb_prop_usage_types,
            "isTrust" =>  $request->isTrust ??  $property->is_trust,
            "trustType" =>  $request->trustType ??  $property->trust_type,
            "isTrustVerified" =>  $request->isTrustVerified ??  $property->is_trust_verified,
            "category" =>  $request->category ??  $property->category_id,
            ];
            $arr =array_merge($arr,$arr2);
        }
        return $arr;
        
    }
    public function generatePropOwnerUpdateRequest(array $request,PropOwner $owners,bool $fullEdit=false)
    {
        $request=(object) $request;
        $arr= [
            "ownerId"=>$owners->id,
            "propId"=>$owners->property_id,
            "safId"=>$owners->saf_id,
            "logs"=>json_encode($owners->toArray(),JSON_UNESCAPED_UNICODE),
            "ownerName" => $request->ownerName ??  $owners->owner_name,  
            "ownerNameMarathi" => $request->ownerNameMarathi ??  $owners->owner_name_marathi, 
            "guardianNameMarathi" => $request->guardianNameMarathi ??  $owners->guardian_name_marathi,
            "guardianName" => $request->guardianName ??  $owners->guardian_name, 
            "relation" => $request->relation ??  $owners->relation_type, 
            "mobileNo" => $request->mobileNo ??  $owners->mobile_no,
            "email" => $request->email ??  $owners->email,
            "pan" => $request->pan ??  $owners->pan_no,
            "aadhar" => $request->aadhar ??  $owners->aadhar_no,
        ];
        $arr2 = [];
        if($fullEdit)
        {
            $arr2=[
            "gender" => $request->gender ?? $owners->gender,
            "dob" =>  $request->dob ?? $owners->dob,
            "isArmedForce" =>  $request->isArmedForce ??  $owners->is_armed_force,
            "isSpeciallyAbled" =>  $request->isSpeciallyAbled ??  $owners->is_specially_abled,
            ];
            $arr =array_merge($arr,$arr2);
        }
        return $arr;
    }

    public function updateProperty(PropPropertyUpdateRequest $UpdateRequest)
    {
        $prop = PropProperty::find($UpdateRequest->prop_id);
        if(!$prop)
        {
            throw new Exception("Property Not Found");
        }

        $arr = $this->updatePropBasic($UpdateRequest);
        if($UpdateRequest->is_full_update)
        {
            $arr =array_merge($arr,$this->updatePropPrimary($UpdateRequest)); 
        }
        return $arr;

    }

    public function updatePropBasic(PropPropertyUpdateRequest $UpdateRequest)
    {
        return $arr= [
            "ulb_id"=> $UpdateRequest->ulb_id,
            "cluster_id" => $UpdateRequest->cluster_id,        
            "applicant_name" =>  $UpdateRequest->applicant_name,
            "applicant_marathi" => $UpdateRequest->applicant_marathi,
            "application_date" =>  $UpdateRequest->application_date,
            "ward_mstr_id" => $UpdateRequest->ward_mstr_id,
            "ownership_type_mstr_id" => $UpdateRequest->ownership_type_mstr_id,
            "no_electric_connection" => $UpdateRequest->no_electric_connection,
            "elect_consumer_no" => $UpdateRequest->elect_consumer_no,
            "elect_acc_no" => $UpdateRequest->elect_acc_no,
            "elect_bind_book_no" =>  $UpdateRequest->elect_bind_book_no,
            "elect_cons_category" =>  $UpdateRequest->elect_cons_category,            
            "water_conn_no" => $UpdateRequest->water_conn_no,  
            "water_conn_date" => $UpdateRequest->water_conn_date, 
            "khata_no" => $UpdateRequest->khata_no,
            "plot_no" =>  $UpdateRequest->plot_no,
            "village_mauja_name" =>  $UpdateRequest->village_mauja_name,   
            "prop_address" => $UpdateRequest->prop_address, 
            "prop_city" =>  $UpdateRequest->prop_city,    
            "prop_dist" => $UpdateRequest->prop_dist,  
            "prop_pin_code" =>  $UpdateRequest->prop_pin_code,  
            "prop_state" => $UpdateRequest->prop_state,  
            "corr_address" =>  $UpdateRequest->corr_address, 
            "corr_city" =>  $UpdateRequest->corr_city,
            "corr_dist" =>  $UpdateRequest->corr_dist,
            "corr_pin_code" =>  $UpdateRequest->corr_pin_code,
            "corr_state" =>  $UpdateRequest->corr_state, 
            "new_ward_mstr_id" =>  $UpdateRequest->new_ward_mstr_id,   
            "entry_type" => $UpdateRequest->entry_type,  
            "zone_mstr_id" => $UpdateRequest->zone_mstr_id,  
            "building_name" =>  $UpdateRequest->building_name, 
            "street_name" => $UpdateRequest->street_name, 
            "location" =>  $UpdateRequest->location, 
            "landmark" =>  $UpdateRequest->landmark,
        ];
    }

    public function updatePropPrimary(PropPropertyUpdateRequest $UpdateRequest)
    {
        return$arr2=[
            "appartment_name" =>  $UpdateRequest->appartment_name,
            "prop_type_mstr_id" =>  $UpdateRequest->prop_type_mstr_id,
            "building_plan_approval_no" =>   $UpdateRequest->building_plan_approval_no,
            "building_plan_approval_date" =>  $UpdateRequest->building_plan_approval_date,
            "road_type_mstr_id" =>   $UpdateRequest->road_type_mstr_id,
            "road_width" =>  $UpdateRequest->road_width,
            "area_of_plot" =>   $UpdateRequest->area_of_plot,
            "is_mobile_tower" =>  $UpdateRequest->is_mobile_tower,
            "tower_area" => $UpdateRequest->tower_area,
            "tower_installation_date"=> $UpdateRequest->tower_installation_date,

            "isHoardingBoard" =>  $UpdateRequest->is_hoarding_board,
            "hoarding_area" => $UpdateRequest->hoarding_area,
            "hoarding_installation_date"=> $UpdateRequest->hoarding_installation_date,
            
            "is_petrol_pump" => $UpdateRequest->is_petrol_pump,
            "under_ground_area"=> $UpdateRequest->under_ground_area,
            "petrol_pump_completion_date"=> $UpdateRequest->petrol_pump_completion_date,

            "is_water_harvesting" =>  $UpdateRequest->is_water_harvesting,            
            "rwh_date_from" =>  $UpdateRequest->rwh_date_from,

            "land_occupation_date" =>  $UpdateRequest->land_occupation_date,
            "flat_registry_date" =>  $UpdateRequest->flat_registry_date,
            "assessment_type" =>  $UpdateRequest->assessment_type,
            "holding_type" =>  $UpdateRequest->holding_type,
            "apartment_details_id" =>  $UpdateRequest->apartment_details_id,
            "is_gb_saf" => $UpdateRequest->is_gb_saf,
            "gb_office_name" =>  $UpdateRequest->gb_office_name,
            "gb_usage_types" =>  $UpdateRequest->gb_usage_types,
            "gb_prop_usage_types" =>  $UpdateRequest->gb_prop_usage_types,
            "is_trust" =>  $UpdateRequest->is_trust,
            "trust_type" => $UpdateRequest->trust_type,
            "is_trust_verified" =>  $UpdateRequest->is_trust_verified,
            "category_id" =>  $UpdateRequest->category_id,
            ];
    }

    public function updatePropOwner(PropOwnerUpdateRequest $UpdateOwnerRequest)
    {
        $PropOwner = PropOwner::find($UpdateOwnerRequest->owner_id);
        $propUdateRequest = PropPropertyUpdateRequest::find($UpdateOwnerRequest->request_id);
        if(!$PropOwner)
        {
            throw new Exception("Owner Not Found");
        }
        $arr = $this->updatePropOwnerBasic($UpdateOwnerRequest);
        if($propUdateRequest->is_full_update)
        {
            $arr =array_merge($arr,$this->updatePropOwnerPrimary($UpdateOwnerRequest)); 
        }
        return $arr;
    }

    public function updatePropOwnerBasic(PropOwnerUpdateRequest $UpdateRequest)
    {
        return $arr= [
                "owner_name" => $UpdateRequest->owner_name,  
                "owner_name_marathi" => $UpdateRequest->owner_name_marathi, 
                "guardian_name_marathi" => $UpdateRequest->guardian_name_marathi,
                "guardian_name" => $UpdateRequest->guardian_name, 
                "relation_type" => $UpdateRequest->relation_type, 
                "mobile_no" => $UpdateRequest->mobile_no,
                "email" => $UpdateRequest->email,
                "pan_no" =>$UpdateRequest->pan_no,
                "aadhar_no" => $UpdateRequest->aadhar_no,
            ];
    }

    public function updatePropOwnerPrimary(PropOwnerUpdateRequest $UpdateRequest)
    {
        return $arr2=[
            "gender" =>  $UpdateRequest->gender,
            "dob" =>  $UpdateRequest->dob,
            "is_armed_force" =>  $UpdateRequest->is_armed_force,
            "is_specially_abled" =>   $UpdateRequest->is_specially_abled,
            ];
    }
}

<?php

namespace App\Traits\Property;

use App\Models\Property\PropProperty;
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
            "logs"=> json_encode($property->toArray()),
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
}

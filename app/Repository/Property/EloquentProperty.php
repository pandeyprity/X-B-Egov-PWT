<?php

namespace App\Repository\Property;

use App\Models\PropFloorDetail;
use App\Models\PropOwner;
use App\Models\PropPropertie;
use App\Models\Saf;
use App\Models\TransferModeMaster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EloquentProperty implements PropertyRepository
{
    /**
     * | Created On-26-08-2022 
     * | Created By-Sandeep Bara
     * ------------------------------------------------------------------------------------------
     * | Property Module all operations 
    */
    public function getPropIdByWardNoHodingNo(array $input)
    {
        try {
            
            $data = PropPropertie::select("prop_properties.id",
                                            "prop_properties.new_holding_no",
                                            "prop_properties.prop_address",
                                            "prop_properties.prop_type_mstr_id",
                                            "owner_name",
                                            "guardian_name",
                                            "mobile_no",                                            
                                            )
                                    ->join('ulb_ward_masters', function($join){
                                        $join->on("ulb_ward_masters.id","=","prop_properties.ward_mstr_id");
                                    })
                                    ->leftJoin(
                                        DB::raw("(SELECT prop_owners.property_id,
                                                        string_agg(prop_owners.owner_name,', ') as owner_name,
                                                        string_agg(prop_owners.guardian_name,', ') as guardian_name,
                                                        string_agg(prop_owners.mobile_no::text,', ') as mobile_no
                                                FROM prop_owners 
                                                WHERE prop_owners.status = 1
                                                GROUP BY prop_owners.property_id
                                                )owner_details
                                                    "),
                                        function($join){
                                            $join->on("owner_details.property_id","=","prop_properties.id")
                                            ;
                                        }
                                    ) 
                                    ->where("prop_properties.ward_mstr_id",$input['ward_mstr_id'])
                                    ->where(function($where)use($input){
                                        $where->orwhere('prop_properties.holding_no', 'ILIKE', '%'.$input['holding_no'].'%')
                                        ->orwhere('prop_properties.new_holding_no', 'ILIKE', '%'.$input['holding_no'].'%');
                                    })
                                    ->get();
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function getPropertyById($id)
    {
        try{
            if(!is_numeric($id))
            {
                $id = Crypt::decryptString($id);
            }
            $data = PropPropertie::select("*")
                            ->where('id',$id)
                            ->first();
            return $data;
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
        
    }
    public function getOwnerDtlByPropId($prop_id)
    {
        try{
            if(!is_numeric($prop_id))
            {
                $prop_id = Crypt::decryptString($prop_id);
            }
            $data = PropOwner::select("*")
                            ->where('status',1)
                            ->where('property_id',$prop_id)
                            ->get();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getFloorDtlByPropId($prop_id)
    {
        try{
            if(!is_numeric($prop_id))
            {
                $prop_id = Crypt::decryptString($prop_id);
            }
            $data = PropFloorDetail::select("*")
                            ->where('status',1)
                            ->where('property_id',$prop_id)
                            ->get();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getAllTransferMode()
    {
        try{
            $data = TransferModeMaster::select("id","transfer_mode")
                                ->where("status",1)
                                ->get();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function transFerSafToProperty($activeSaf,$current_user_id)
    { 
        
         try{
            $saf = new Saf();
            $saf->has_previous_holding_no = $activeSaf->has_previous_holding_no;
            $saf->previous_holding_id     = $activeSaf->previous_holding_id; 
            $saf->previous_ward_mstr_id   = $activeSaf->previous_holding_id; 
            $saf->transfer_mode_mstr_id   = $activeSaf->transfer_mode_mstr_id;
            $saf->saf_no                  = $activeSaf->saf_no;
            $saf->holding_no              = $activeSaf->holding_no;
            $saf->ward_mstr_id            = $activeSaf->ward_mstr_id;
            $saf->ownership_type_mstr_id  = $activeSaf->ownership_type_mstr_id;
            $saf->prop_type_mstr_id       = $activeSaf->prop_type_mstr_id;
            $saf->appartment_name         = $activeSaf->appartment_name;
            $saf->flat_registry_date      = $activeSaf->flat_registry_date;
            $saf->zone_mstr_id            = $activeSaf->zone_mstr_id ;
            $saf->no_electric_connection  = $activeSaf->no_electric_connection;
            $saf->elect_consumer_no       = $activeSaf->elect_consumer_no ;
            $saf->elect_acc_no            = $activeSaf->elect_acc_no;
            $saf->elect_bind_book_no      = $activeSaf->elect_bind_book_no;
            $saf-> elect_cons_category    = $activeSaf-> elect_cons_category ;  
            $saf-> building_plan_approval_no    = $activeSaf-> building_plan_approval_no;  
            $saf-> building_plan_approval_date  = $activeSaf-> building_plan_approval_date;  
            $saf->water_conn_no           = $activeSaf->water_conn_no;  
            $saf-> water_conn_date        = $activeSaf-> water_conn_date;  
            $saf->khata_no                = $activeSaf->khata_no ;  
            $saf->plot_no                 = $activeSaf->plot_no;  
            $saf->village_mauja_name      = $activeSaf->village_mauja_name;  
            $saf->road_type_mstr_id       = $activeSaf->road_type_mstr_id ;  
            $saf->area_of_plot            = $activeSaf->area_of_plot;  
            $saf->prop_address            = $activeSaf->prop_address;  
            $saf->prop_city               = $activeSaf->prop_city;  
            $saf-> prop_dist              = $activeSaf-> prop_dist;  
            $saf->prop_pin_code           = $activeSaf->prop_pin_code;  
            $saf->is_corr_add_differ      = $activeSaf->is_corr_add_differ;  
            $saf-> corr_address           = $activeSaf-> corr_address;  
            $saf->corr_city               = $activeSaf->corr_city;  
            $saf->corr_dist               = $activeSaf->corr_dist;  
            $saf-> corr_pin_code          = $activeSaf-> corr_pin_code;  
            $saf-> is_mobile_tower        = $activeSaf-> is_mobile_tower;  
            $saf->tower_area              = $activeSaf->tower_area;  
            $saf->tower_installation_date = $activeSaf->tower_installation_date;  
            $saf->is_hoarding_board       = $activeSaf->is_hoarding_board;  
            $saf->hoarding_area           = $activeSaf->hoarding_area;  
            $saf-> hoarding_installation_date = $activeSaf-> hoarding_installation_date;  
            $saf-> is_petrol_pump         = $activeSaf-> is_petrol_pump;  
            $saf->under_ground_area       = $activeSaf->under_ground_area;  
            $saf->petrol_pump_completion_date = $activeSaf->petrol_pump_completion_date;  
            $saf->is_water_harvesting     = $activeSaf->is_water_harvesting;  
            $saf->land_occupation_date    = $activeSaf->land_occupation_date;  
            $saf->payment_status          = $activeSaf->payment_status;  
            $saf-> doc_verify_status      = $activeSaf-> doc_verify_status;  
            $saf->doc_verify_date         = $activeSaf->doc_verify_date;  
            $saf->doc_verify_emp_details_id = $activeSaf->doc_verify_emp_details_id;  
            $saf->doc_verify_cancel_remarks = $activeSaf->doc_verify_cancel_remarks;  
            $saf-> field_verify_status    = $activeSaf-> field_verify_status;  
            $saf-> field_verify_date      = $activeSaf-> field_verify_date;  
            $saf->field_verify_emp_details_id  = $activeSaf->field_verify_emp_details_id;  
            $saf->emp_details_id          = $activeSaf->emp_details_id;  
            $saf->status                  = $activeSaf->status;  
            $saf-> apply_date             = $activeSaf-> apply_date;  
            $saf-> saf_pending_status     = $activeSaf-> saf_pending_status;  
            $saf->assessment_type         = $activeSaf->assessment_type;  
            $saf-> doc_upload_status      = $activeSaf-> doc_upload_status;  
            $saf->saf_distributed_dtl_id  = $activeSaf->saf_distributed_dtl_id;  
            $saf-> prop_dtl_id            = $activeSaf-> prop_dtl_id;  
            $saf-> prop_state             = $activeSaf-> prop_state;  
            $saf->corr_state              = $activeSaf->corr_state ;  
            $saf->holding_type            = $activeSaf->holding_type;  
            $saf-> ip_address             = $activeSaf-> ip_address;  
            $saf-> property_assessment_id = $activeSaf-> property_assessment_id;  
            $saf->new_ward_mstr_id        = $activeSaf->new_ward_mstr_id;  
            $saf-> percentage_of_property_transfer = $activeSaf-> percentage_of_property_transfer;  
            $saf->apartment_details_id    = $activeSaf->apartment_details_id;  
            $saf->current_user            = $activeSaf->current_user;  
            $saf->initiator_id            = $activeSaf->initiator_id;  
            $saf-> finisher_id            = $activeSaf-> finisher_id;  
            $saf->workflow_id             = $activeSaf->workflow_id;  
            $saf-> ulb_id                 = $activeSaf-> ulb_id;  
            $saf->is_escalate             = $activeSaf->is_escalate;  
            $saf-> citizen_id             = $activeSaf-> citizen_id;  
            $saf->escalate_by             = $activeSaf->escalate_by;  
            $saf->deleted_at              = $activeSaf->deleted_at;  
            $saf->created_at              = $activeSaf->created_at;  
            $saf->updated_at              = $activeSaf->updated_at; 
            $saf->save();
            $saf_id = $activeSaf->id; 
            $id =  $saf->id;
            $sql ="insert into saf_owners_details
                (   saf_dtl_id ,owner_name ,guardian_name ,relation_type ,mobile_no ,email ,pan_no ,aadhar_no ,emp_details_id ,
                    created_on ,status ,rmc_saf_owner_dtl_id ,rmc_saf_dtl_id ,gender ,dob ,is_armed_force ,is_specially_abled ,
                    created_at ,updated_at
                )
                select 
                    $id ,owner_name ,guardian_name ,relation_type ,mobile_no ,email ,pan_no ,aadhar_no ,emp_details_id ,
                    created_on ,status ,rmc_saf_owner_dtl_id ,rmc_saf_dtl_id ,gender ,dob ,is_armed_force ,is_specially_abled ,
                    created_at ,updated_at
                from active_saf_owner_details
                where saf_dtl_id = $saf_id ";
            DB::insert($sql);
            $sql ="insert into saf_floor_details
                (   saf_dtl_id ,floor_mstr_id ,usage_type_mstr_id ,const_type_mstr_id  ,occupancy_type_mstr_id ,builtup_area ,date_from ,date_upto ,emp_details_id ,
                    status ,carpet_area ,prop_floor_details_id ,created_at ,updated_at 
                )
                select 
                    $id ,floor_mstr_id ,usage_type_mstr_id ,const_type_mstr_id  ,occupancy_type_mstr_id ,builtup_area ,date_from ,date_upto ,emp_details_id ,
                    status ,carpet_area ,prop_floor_details_id ,created_at ,updated_at
                from active_saf_floor_details
                where saf_dtl_id = $saf_id ";
            DB::insert($sql);
            $sql ="insert into saf_taxes
                (   saf_dtl_id ,fy_mstr_id ,arv ,holding_tax  ,water_tax ,education_cess ,health_cess ,latrine_tax ,additional_tax ,
                    created_on ,status ,qtr ,rmc_saf_tax_dtl_id ,rmc_saf_dtl_id,fyear,quarterly_tax,created_at,updated_at 
                )
                select 
                    $id,fy_mstr_id ,arv ,holding_tax  ,water_tax ,education_cess ,health_cess ,latrine_tax ,additional_tax ,
                    created_on ,status ,qtr ,rmc_saf_tax_dtl_id ,rmc_saf_dtl_id,fyear,quarterly_tax,created_at,updated_at 
                from active_saf_taxes
                where saf_dtl_id = $saf_id ";
            DB::insert($sql);
            $holdingNo = $this->getHoldingNo($saf->prop_pin_code,$saf->ward_mstr_id,$saf->ulb_id);
            $property = new PropPropertie();
            $property->saf_id                       = $id ;
            $property->assessment_type              = $saf->assessment_type ;
            $property->holding_type                 = $saf->holding_type;   
            $property->holding_no                   = $holdingNo;
            $property->new_holding_no               = $saf->new_holding_no;
            $property->ward_mstr_id                 = $saf->ward_mstr_id; 
            $property->zone_mstr_id                 = $saf->zone_mstr_id ;
            $property->new_ward_mstr_id             = $saf->new_ward_mstr_id;
            $property->ownership_type_mstr_id       = $saf->ownership_type_mstr_id ;
            $property->prop_type_mstr_id            = $saf->prop_type_mstr_id;
            $property->appartment_name              = $saf->appartment_name ;
            $property->no_electric_connection       = $saf->no_electric_connection;
            $property->elect_consumer_no            = $saf->elect_consumer_no;
            $property->elect_acc_no                 = $saf->elect_acc_no;
            $property->elect_bind_book_no           = $saf->elect_bind_book_no;
            $property->elect_cons_category          = $saf->elect_cons_category;
            $property->building_plan_approval_no    = $saf->building_plan_approval_no;
            $property->building_plan_approval_date  = $saf->building_plan_approval_date;
            $property->water_conn_no                = $saf->water_conn_no;
            $property->water_conn_date              = $saf->water_conn_date;
            $property->khata_no                     = $saf->khata_no;
            $property->plot_no                      = $saf->plot_no;
            $property->village_mauja_name           = $saf->village_mauja_name ;
            $property->road_type_mstr_id            = $saf->road_type_mstr_id;
            $property->area_of_plot                 = $saf->area_of_plot ;
            $property->prop_address                 = $saf->prop_address;
            $property->prop_city                    = $saf->prop_city;
            $property->prop_dist                    = $saf->prop_dist;
            $property->prop_pin_code                = $saf->prop_pin_code;
            $property->corr_address                 = $saf->corr_address;
            $property->corr_city                    = $saf->corr_city;
            $property->corr_dist                    = $saf->corr_dist;
            $property->corr_pin_code                = $saf->corr_pin_code;
            $property->is_mobile_tower              = $saf->is_mobile_tower;
            $property->tower_area                   = $saf->tower_area;
            $property->tower_installation_date      = $saf->tower_installation_date;
            $property->is_hoarding_board            = $saf->is_hoarding_board;
            $property->hoarding_area                = $saf->hoarding_area ;
            $property->hoarding_installation_date   = $saf->hoarding_installation_date ;
            $property->is_petrol_pump               = $saf->is_petrol_pump;
            $property->under_ground_area            = $saf->under_ground_area;
            $property->petrol_pump_completion_date  = $saf->petrol_pump_completion_date;
            $property->is_water_harvesting          = $saf->is_water_harvesting;
            $property->occupation_date              = $saf->occupation_date;
            $property->emp_details_id               = $current_user_id;
            $property->status                       = $saf->status;
            $property->saf_hold_status              = $saf->saf_hold_status;
            $property->prop_state                   = $saf->prop_state;
            $property->corr_state                   = $saf->corr_state;
            $property->flat_registry_date           = $saf->flat_registry_date;
            $property->apartment_details_id         = $saf->apartment_details_id;
            $property->application_date             = $saf->application_date;
            $property->ulb_id                       = $saf->ulb_id;
            $property->save();
            $prop_id = $property->id;

            $sql ="insert into prop_owners
                (   property_id,owner_name ,guardian_name ,relation_type ,mobile_no ,email ,pan_no ,aadhar_no ,emp_details_id ,
                    created_on ,status ,gender ,dob ,is_armed_force ,is_specially_abled ,
                    created_at ,updated_at
                )
                select 
                    $prop_id,owner_name ,guardian_name ,relation_type ,mobile_no ,email ,pan_no ,aadhar_no ,emp_details_id ,
                    created_on ,status  ,gender ,dob ,is_armed_force ,is_specially_abled ,
                    now() ,updated_at
                from active_saf_owner_details
                where saf_dtl_id = $saf_id ";
            DB::insert($sql);
            $sql ="insert into prop_floor_details
                (   property_id,saf_dtl_id ,floor_mstr_id ,usage_type_mstr_id ,const_type_mstr_id  ,occupancy_type_mstr_id ,builtup_area ,date_from ,date_upto ,emp_details_id ,
                    status ,carpet_area ,prop_floor_details_id ,created_at ,updated_at 
                )
                select 
                $prop_id,$id ,floor_mstr_id ,usage_type_mstr_id ,const_type_mstr_id  ,occupancy_type_mstr_id ,builtup_area ,date_from ,date_upto ,emp_details_id ,
                    status ,carpet_area ,prop_floor_details_id ,now() ,updated_at
                from active_saf_floor_details
                where saf_dtl_id = $saf_id ";
            DB::insert($sql); 
            return['property_id'=>$prop_id,"holding_no"=>$holdingNo,"saf_id"=>$id,"active_id"=>$saf_id];
        }
        catch(Exception $e)
        { 
            return false;
        } 
        
    }


    public function getHoldingNo($pincode,$ward_id,$ulb_id)
    {
        $count = PropPropertie::where('ward_mstr_id',$ward_id)
                                ->where('ulb_id',$ulb_id)
                                ->where('status',1)
                                ->count()+1;
        $holdingNo = date('y').$pincode.str_pad($count,6,'0',STR_PAD_LEFT);
        $ckPoint = $this->getChekSum( $holdingNo);
        $holdingNo .= str_pad(( ($ckPoint%9)==0 ? 9 : ($ckPoint%9)),2,'0',STR_PAD_LEFT);
        return $holdingNo;
    }
    public function getChekSum($holdingNo)
    {
        if(strlen($holdingNo)>1)
        {
            return $this->getChekSum($holdingNo/10)+$holdingNo%10;
        }
        else 
            return $holdingNo;
    }
}
<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSaf extends Model
{
    use HasFactory;

    /**
     * | 
     */
    public function getSafDtlsBySafNo($safNo)
    {
        return DB::table('prop_safs as s')
            ->where('s.saf_no', strtoupper($safNo))
            ->select(
                's.id',
                DB::raw("'approved' as status"),
                's.saf_no',
                's.ward_mstr_id',
                's.new_ward_mstr_id',
                's.elect_consumer_no',
                's.elect_acc_no',
                's.elect_bind_book_no',
                's.elect_cons_category',
                's.prop_address',
                's.corr_address',
                's.prop_pin_code',
                's.corr_pin_code',
                's.assessment_type',
                's.applicant_name',
                's.application_date',
                's.area_of_plot as total_area_in_decimal',
                's.prop_type_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'p.property_type',
                'doc_upload_status',
                'payment_status',
                DB::raw(
                    "case when payment_status!=1 then 'Payment Not Done'
                          else role_name end
                          as current_role
                    "
                ),
                'role_name as approvedBy',
                's.user_id',
                's.citizen_id',
                DB::raw(
                    "case when s.user_id is not null then 'TC/TL/JSK' when 
                    s.citizen_id is not null then 'Citizen' end as appliedBy
                "
                ),
            )
            ->leftjoin('wf_roles', 'wf_roles.id', 's.current_role')
            ->join('ulb_ward_masters as u', 's.ward_mstr_id', '=', 'u.id')
            ->join('ref_prop_types as p', 'p.id', '=', 's.prop_type_mstr_id')
            ->leftJoin('ulb_ward_masters as u1', 's.new_ward_mstr_id', '=', 'u1.id')
            ->first();
    }

    /**
     * | Get GB SAf details by saf No
     */
    public function getGbSafDtlsBySafNo($safNo)
    {
        return DB::table('prop_safs as s')
            ->where('s.saf_no', strtoupper($safNo))
            ->select(
                's.id',
                DB::raw("'approved' as status"),
                's.saf_no',
                's.ward_mstr_id',
                's.new_ward_mstr_id',
                's.prop_address',
                's.prop_pin_code',
                's.assessment_type',
                's.applicant_name',
                's.application_date',
                's.area_of_plot as total_area_in_decimal',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'doc_upload_status',
                'payment_status',
                DB::raw(
                    "case when payment_status!=1 then 'Payment Not Done'
                          else role_name end
                          as current_role
                    "
                ),
                'role_name as approvedBy',
                's.user_id',
                's.citizen_id',
                'gb_office_name',
                'building_type',
                DB::raw(
                    "case when s.user_id is not null then 'TC/TL/JSK' when 
                    s.citizen_id is not null then 'Citizen' end as appliedBy
                "
                ),
            )
            ->join('wf_roles', 'wf_roles.id', 's.current_role')
            ->leftjoin('ref_prop_gbpropusagetypes as p', 'p.id', '=', 's.gb_usage_types')
            ->leftjoin('ref_prop_gbbuildingusagetypes as q', 'q.id', '=', 's.gb_prop_usage_types')
            ->join('ulb_ward_masters as u', 's.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 's.new_ward_mstr_id', '=', 'u1.id')
            ->first();
    }

    /**
     * | Search safs
     */
    public function searchSafs()
    {
        return PropSaf::select(
            'prop_safs.id',
            DB::raw("'approved' as status"),
            'prop_safs.saf_no',
            'prop_safs.assessment_type',
            DB::raw(
                "case when prop_safs.payment_status = 0 then 'Payment Not Done'
                      when prop_safs.payment_status = 2 then 'Cheque Payment Verification Pending'
                      else role_name end
                      as current_role
                "
            ),
            'role_name as currentRole',
            'u.ward_name as old_ward_no',
            'uu.ward_name as new_ward_no',
            'prop_address',
            DB::raw(
                "case when prop_safs.user_id is not null then 'TC/TL/JSK' when 
                prop_safs.citizen_id is not null then 'Citizen' end as appliedBy"
            ),
            DB::raw("string_agg(so.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(so.owner_name,',') as owner_name"),
        )
            ->leftjoin('wf_roles', 'wf_roles.id', 'prop_safs.current_role')
            ->join('ulb_ward_masters as u', 'u.id', 'prop_safs.ward_mstr_id')
            ->leftjoin('ulb_ward_masters as uu', 'uu.id', 'prop_safs.new_ward_mstr_id')
            ->join('prop_safs_owners as so', 'so.saf_id', 'prop_safs.id');
    }

    /**
     * | Search Gb Saf
     */
    public function searchGbSafs()
    {
        return PropSaf::select(
            'prop_safs.id',
            DB::raw("'approved' as status"),
            'prop_safs.saf_no',
            'prop_safs.assessment_type',
            DB::raw(
                "case when prop_safs.payment_status!=1 then 'Payment Not Done'
                      else role_name end
                      as current_role
                "
            ),
            'role_name as currentRole',
            'ward_name as old_ward_no',
            'prop_address',
            'gbo.officer_name',
            'gbo.mobile_no'
        )
            ->leftjoin('wf_roles', 'wf_roles.id', 'prop_safs.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_safs.ward_mstr_id')
            ->join('prop_gbofficers as gbo', 'gbo.saf_id', 'prop_safs.id');
    }

    /**
     * | Get Saf Details
     */
    public function getSafDtls()
    {
        return DB::table('prop_safs')
            ->select(
                'prop_safs.*',
                'prop_safs.assessment_type as assessment',
                DB::raw("REPLACE(prop_safs.holding_type, '_', ' ') AS holding_type"),
                'w.ward_name as old_ward_no',
                'nw.ward_name as new_ward_no',
                'o.ownership_type',
                'p.property_type',
                'r.road_type as road_type_master',
                'wr.role_name as current_role_name',
                't.transfer_mode',
                'a.apt_code as apartment_code',
                'a.apartment_address',
                'a.no_of_block',
                'a.apartment_name',
                'building_type',
                'prop_usage_type',
                'zone'
            )
            ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'prop_safs.ward_mstr_id')
            ->leftJoin('wf_roles as wr', 'wr.id', '=', 'prop_safs.current_role')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'prop_safs.new_ward_mstr_id')
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 'prop_safs.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as p', 'p.id', '=', 'prop_safs.prop_type_mstr_id')
            ->leftJoin('ref_prop_road_types as r', 'r.id', '=', 'prop_safs.road_type_mstr_id')
            ->leftJoin('ref_prop_transfer_modes as t', 't.id', '=', 'prop_safs.transfer_mode_mstr_id')
            ->leftJoin('prop_apartment_dtls as a', 'a.id', '=', 'prop_safs.apartment_details_id')
            ->leftJoin('zone_masters', 'zone_masters.id', 'prop_safs.zone_mstr_id')
            ->leftJoin('ref_prop_gbbuildingusagetypes as gbu', 'gbu.id', 'prop_safs.gb_usage_types')
            ->leftJoin('ref_prop_gbpropusagetypes as gbp', 'gbp.id', 'prop_safs.gb_prop_usage_types');
    }

    /**
     * | get Safs details from prop id
     */
    public function getSafbyPropId($propId)
    {
        return PropSaf::where('property_id', $propId)
            ->first();
    }

    /**
     * | Count Previous Holdings
     */
    public function countPreviousHoldings($previousHoldingId)
    {
        return PropSaf::where('previous_holding_id', $previousHoldingId)
            ->count();
    }
}

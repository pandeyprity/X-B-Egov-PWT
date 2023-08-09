<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSafVerification extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getVerificationsData($safId)
    {
        return DB::table('prop_saf_verifications as v')
            ->select(
                'v.id',
                'v.saf_id',
                'v.agency_verification',
                'v.emp_id',
                'v.prop_type_id as prop_type_mstr_id',
                'v.road_type_id as road_type_mstr_id',
                'v.area_of_plot',
                'v.verified_by',
                'v.ward_id as ward_mstr_id',
                'v.has_mobile_tower as is_mobile_tower',
                'v.tower_area',
                'v.tower_installation_date',
                'v.has_hoarding as is_hoarding_board',
                'v.hoarding_area',
                'v.hoarding_installation_date',
                'v.is_petrol_pump',
                'v.underground_area as under_ground_area',
                'v.petrol_pump_completion_date',
                'v.has_water_harvesting as is_water_harvesting',
                'v.created_at',
                'v.updated_at',
                'v.status',
                'v.user_id',
                'v.percentage_of_property_transfer',
                'v.new_ward_id as new_ward_mstr_id',
                'v.ulb_id',
                'v.old_verification_id',
                'v.road_width',
                'v.rwh_date_from',
                'p.property_type',
                'r.road_type as road_type_master',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'building_type',
                'prop_usage_type'
            )
            ->join('ref_prop_road_types as r', 'r.id', '=', 'v.road_type_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'v.ward_id')
            ->leftjoin('ref_prop_types as p', 'p.id', '=', 'v.prop_type_id')
            ->leftJoin('ulb_ward_masters as u1', 'u1.id', '=', 'v.new_ward_id')
            ->leftJoin('ref_prop_gbbuildingusagetypes as gbu', 'gbu.id', 'v.gb_usage_types')
            ->leftJoin('ref_prop_gbpropusagetypes as gbp', 'gbp.id', 'v.gb_prop_usage_types')
            ->where('v.saf_id', $safId)
            ->where('v.agency_verification', true)
            ->orderByDesc('id')
            ->first();
    }

    // Store
    public function store($req)
    {
        $metaReqs = [
            'saf_id' => $req->safId,
            'agency_verification' => $req->agencyVerification ?? null,
            'ulb_verification' => $req->ulbVerification ?? null,
            'prop_type_id' => $req->propertyType,
            'road_type_id' => $req->roadType,
            'road_width' => $req->roadWidth,
            'area_of_plot' => $req->areaOfPlot,
            'ward_id' => $req->wardId,
            'new_ward_id' => $req->newWardId,
            'has_mobile_tower' => $req->isMobileTower,
            'tower_area' => $req->mobileTower['area'],
            'tower_installation_date' => $req->mobileTower['dateFrom'],
            'has_hoarding' => $req->isHoardingBoard,
            'hoarding_area' => $req->hoardingBoard['area'],
            'hoarding_installation_date' => $req->hoardingBoard['dateFrom'],
            'is_petrol_pump' => $req->isPetrolPump,
            'underground_area' => $req->petrolPump['area'],
            'petrol_pump_completion_date' => $req->petrolPump['dateFrom'],
            'has_water_harvesting' => $req->isWaterHarvesting,
            'user_id' => $req->userId,
            'ulb_id' => $req->ulbId,
            'gb_usage_types' => $req->gbUsageTypes,
            'gb_prop_usage_types' => $req->gbPropUsageTypes,
            'rwh_date_from' => $req->rwhDateFrom
        ];

        return PropSafVerification::create($metaReqs)->id;
    }

    /**
     * | Deactivate Verifications
     */
    public function deactivateVerifications($safId)
    {
        $safVerifications = PropSafVerification::where('saf_id', $safId)
            ->get();

        collect($safVerifications)->map(function ($safVerification) {
            $safVerification->status = 0;
            $safVerification->save();
        });
    }

    /**
     * | Get Ulb Field Verification by SafId
     */
    public function getVerificationsBySafId($safId)
    {
        $query = "SELECT *,v.id as id FROM prop_saf_verification_dtls AS v
                    JOIN (SELECT * FROM prop_saf_verifications WHERE saf_id=$safId AND ulb_verification=TRUE ORDER BY id DESC LIMIT 1) AS p ON p.id=v.verification_id
                    WHERE v.saf_id=$safId";
        return DB::select($query);
    }

    /**
     * | Get Verifications
     */
    public function getVerifications($safId)
    {
        $query = "SELECT * FROM prop_saf_verifications WHERE saf_id=$safId AND ulb_verification=TRUE ORDER BY id DESC LIMIT 1";
        return DB::select($query);
    }
}

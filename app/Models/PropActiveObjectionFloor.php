<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveObjectionFloor extends Model
{
    use HasFactory;

    /**
     * | 
     */
    public function  getfloorObjectionId($objId)
    {
        return PropActiveObjectionFloor::where('objection_id', $objId)
            ->join('ref_prop_usage_types', 'ref_prop_usage_types.id', 'prop_active_objection_floors.usage_type_mstr_id')
            ->join('ref_prop_floors', 'ref_prop_floors.id', 'prop_active_objection_floors.floor_mstr_id')
            ->join('ref_prop_occupancy_types', 'ref_prop_occupancy_types.id', 'prop_active_objection_floors.occupancy_type_mstr_id')
            ->join('ref_prop_construction_types', 'ref_prop_construction_types.id', 'prop_active_objection_floors.const_type_mstr_id')
            ->get();
    }
}

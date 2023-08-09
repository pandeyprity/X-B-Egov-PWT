<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropActiveObjectionDtl extends Model
{
    use HasFactory;

    /**
     * 
     */
    public function getDtlbyObjectionId($objId)
    {
        return PropActiveObjectionDtl::where('objection_id', $objId)
            ->join('ref_prop_objection_types', 'ref_prop_objection_types.id', 'prop_active_objection_dtls.objection_type_id')
            ->get();
    }
}

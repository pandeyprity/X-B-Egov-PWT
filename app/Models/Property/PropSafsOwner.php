<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropSafsOwner extends Model
{
    use HasFactory;

    /**
     * | Get Owner by Saf No
     */
    public function getOwnerDtlsBySafId1($safId)
    {
        return PropSafsOwner::where('saf_id', $safId)
            ->select(
                'owner_name',
                'mobile_no',
                'dob',
                'guardian_name',
                'email',
                'is_armed_force',
                'is_specially_abled'
            )
            ->orderBy('id')
            ->first();
    }


    public function getOwnersBySafId($safId)
    {
        return PropSafsOwner::select(
            'prop_safs_owners.*'
        )
            ->where('saf_id', $safId)
            ->where('status', 1)
            ->get();
    }
}

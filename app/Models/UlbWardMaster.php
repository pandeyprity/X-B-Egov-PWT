<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UlbWardMaster extends Model
{
    use HasFactory;
    public $timestamps = false;
    /**
     * | Get the Ward No by ward id
     * | @param id $id
     */
    public function getWardById($id)
    {
        return UlbWardMaster::find($id);
    }

    /**
     * | Get Ward By Ulb ID
     * | @param ulbId
     */
    public function getWardByUlbId($ulbId)
    {
        return UlbWardMaster::select('id', 'ward_name')
            ->where('ulb_id', $ulbId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | get the ward by Id
     * | @param id
     */
    public function getWard($id)
    {
        return UlbWardMaster::where('id', $id)
            ->firstOrFail();
    }

    /**
     * | get the ward by Id
     * | @param id
     */
    public function getExistWard($id)
    {
        return UlbWardMaster::where('id', $id)
            ->first();
    }
}

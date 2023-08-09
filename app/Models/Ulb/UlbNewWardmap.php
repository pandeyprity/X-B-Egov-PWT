<?php

namespace App\Models\Ulb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UlbNewWardmap extends Model
{
    use HasFactory;

    /**
     * |
     */
    public function getNewWardByOldWard($req)
    {
        return UlbNewWardmap::select(
            'ulb_new_wardmaps.id',
            'ulb_new_wardmaps.new_ward_mstr_id',
            'ward_name'
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'ulb_new_wardmaps.new_ward_mstr_id')
            ->where('old_ward_mstr_id', $req->oldWardMstrId)
            ->orderBy('new_ward_mstr_id')
            ->get();
    }
}

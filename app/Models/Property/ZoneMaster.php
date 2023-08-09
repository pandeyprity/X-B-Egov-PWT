<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneMaster extends Model
{
    use HasFactory;

    public function getZone($ulbId)
    {
        return ZoneMaster::select('id', 'zone')
            ->where('ulb_id', $ulbId)
            ->get();
    }
}

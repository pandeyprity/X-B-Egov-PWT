<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneMaster extends Model
{
    use HasFactory;

    public function getZone()
    {
        return ZoneMaster::select('id', 'zone_name')
            ->where('status', 1)
            ->get();
    }
}

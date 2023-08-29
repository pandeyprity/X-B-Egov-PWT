<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConnectionTypeMstr extends Model
{
    use HasFactory;

    /**
     * | Get Connection type
     */
    public function getWaterConnectionTypeMstr()
    {
        return WaterConnectionTypeMstr::select(
            'id',
            'connection_type'
        )
            ->where('status', 1)
            ->get();
    }
}

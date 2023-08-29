<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterOwnerTypeMstr extends Model
{
    use HasFactory;

    /**
     * | Get Owner Type List
     */
    public function getWaterOwnerTypeMstr()
    {
        return WaterOwnerTypeMstr::select(
            'id',
            'owner_type'
        )
            ->where('status', 1)
            ->get();
    }
}

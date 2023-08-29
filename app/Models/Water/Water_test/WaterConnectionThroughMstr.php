<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConnectionThroughMstr extends Model
{
    use HasFactory;


    /**
     * | GEt the  Connection through
     */
    public function getWaterConnectionThroughMstr()
    {
        return WaterConnectionThroughMstr::select(
            'id',
            'connection_through'
        )
            ->where('status', 1)
            ->get();
    }
}

<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPropertyTypeMstr extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get Property type Details
     */
    public function getWaterPropertyTypeMstr()
    {
        return WaterPropertyTypeMstr::select(
            'id',
            'property_type'
        )
            ->where('status', 1)
            ->get();
    }
}

<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterParamDemandCharge extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get charges for the consumer details 
     */
    public function getConsumerCharges($callParameters)
    {
        return WaterParamDemandCharge::where('property_type_id', $callParameters->propertyType)
            ->where('area_catagory_id', $callParameters->areaCatagory)
            ->where('connection_size', $callParameters->connectionSize)
            ->where('is_meter', $callParameters->meterState)
            ->where('status', 1)
            ->first();
    }
}

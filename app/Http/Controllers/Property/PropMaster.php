<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\RefPropUsageType;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropRoadType;
use Illuminate\Http\Request;

class PropMaster extends Controller
{
    //usage type
    public function propUsageType()
    {
        $obj = new RefPropUsageType();
        return $obj->propUsageType();
    }

    //constrction type
    public function propConstructionType()
    {
        $obj = new RefPropConstructionType();
        return $obj->propConstructionType();
    }

    //occupancy type
    public function propOccupancyType()
    {
        $obj = new RefPropOccupancyType();
        return $obj->propOccupancyType();
    }

    //property type
    public function propPropertyType()
    {
        $obj = new RefPropType();
        return $obj->propPropertyType();
    }

    //road type
    public function propRoadType()
    {
        $obj = new RefPropRoadType();
        return $obj->propRoadType();
    }
}

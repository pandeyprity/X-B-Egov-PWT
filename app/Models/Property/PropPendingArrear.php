<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropPendingArrear extends Model
{
    use HasFactory;

    /**
     * | Get Property Interest
     */
    public function getInterestByPropId($propId)
    {
        return self::select(
            "property_id",
            "fyear",
            "property_tax",
            "education_tax",
            "tree_cess",
            "tax1",
            "employment_tax",
            "fire_cess",
            "sp_education_tax",
            "light_cess",
            "road_cess",
            "sewage_disposal_cess",
            "sp_water_cess",
            "water_benefit",
            "water_bill",
            "tax2",
            "total_interest",
            "tax_total",
            "paid_status",
            "status"
        )
            ->where('status', 1)
            ->where('paid_status', 0)
            ->where('property_id', $propId)
            ->get();
    }
}

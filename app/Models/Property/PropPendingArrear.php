<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropPendingArrear extends Model
{
    use HasFactory;

    /**
     * | 
     */
    public function getInterestByPropId($propId)
    {
        return self::select('total_interest')
            ->where('status', 1)
            ->where('prop_id', $propId)
            ->first();
    }
}

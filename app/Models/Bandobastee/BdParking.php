<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdParking extends Model
{
    use HasFactory;
    
    /**
     * | Get Parking List
     */
    public function listParking($ulbId)
    {
        return BdParking::select('id', 'parking_name')
            ->where('ulb_id', $ulbId)
            ->orderBy('id', 'ASC')
            ->get();
    }
}

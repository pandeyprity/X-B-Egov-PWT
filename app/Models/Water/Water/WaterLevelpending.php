<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterLevelpending extends Model
{
    use HasFactory;

    public function getReceiverLevel($concessionId, $senderRoleId)
    {
        return  WaterLevelpending::where('water_application_id', $concessionId)
            ->where('receiver_role_id', $senderRoleId)
            ->first();
    }


    public function getLevelsByApplicationId($id)
    {
        return WaterLevelpending::where('water_application_id', $id)
            ->get();
    }
}

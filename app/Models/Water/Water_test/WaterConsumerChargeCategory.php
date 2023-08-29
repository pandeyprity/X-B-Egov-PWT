<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerChargeCategory extends Model
{
    use HasFactory;

    /**
     * | Get all active consumer cherges
     */
    public function getConsumerChargesType()
    {
        return WaterConsumerChargeCategory::select(
            'id',
            'charge_category'
        )
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get charge catagory according to id
     */
    public function getChargesByid($id)
    {
        return WaterConsumerChargeCategory::where('id', $id)
            ->where('status', 1)
            ->first();
    }

}

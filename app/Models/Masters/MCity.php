<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MCity extends Model
{
    use HasFactory;

    /**
     * | Get City and State by Ulb Id
     */
    public function getCityStateByUlb($ulbId)
    {
        return DB::table("ulb_masters")
            ->select("ulb_masters.*", "c.city_name", "s.name")
            ->join("m_cities as c", 'c.ulb_id', '=', 'ulb_masters.id')
            ->join("m_states as s", "s.id", '=', 'c.state_id')
            ->where("ulb_masters.id", $ulbId)
            ->first();
    }
}

<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdBanquetHall extends Model
{
    use HasFactory;
    
    /**
     * | List of Banquet Hall
     */
    public function listBanquetHall($ulbId)
    {
        return BdBanquetHall::select('id', 'banquet_hall_name')
            ->where('ulb_id', $ulbId)
            ->orderBy('id', 'ASC')
            ->get();
    }
}

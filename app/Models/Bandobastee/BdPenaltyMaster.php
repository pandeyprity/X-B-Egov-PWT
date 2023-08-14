<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdPenaltyMaster extends Model
{
    use HasFactory;
    
    /**
     * | Get penalty List
     */
    public function listPenalty()
    {
        return BdPenaltyMaster::select('id', 'penalty_name')
            ->where('status', '1')
            ->get();
    }
}

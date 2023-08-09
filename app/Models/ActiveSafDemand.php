<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveSafDemand extends Model
{
    use HasFactory;
    protected $table = 'prop_safs_demands';

    /**
     * | Get Last Demand Date by Saf Id
     */
    public function readLastDemandDateBySafId($safId)
    {
        $safDemand = ActiveSafDemand::where('saf_id', $safId)
            ->orderByDesc('id')
            ->first();
        return $safDemand;
    }
}

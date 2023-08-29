<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdMaster extends Model
{
    use HasFactory;
    
    /**
     * | Get Bandobastee list
     */
    public function listMaster()
    {
        return BdMaster::select('id', 'bandobastee_name')
            ->where(['status' => '1'])
            ->orderBy('id')
            ->get();
    }
}

<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdBazar extends Model
{
    use HasFactory;

    /**
     * | Get Bazar List
     */
    public function listBazar($ulbId)
    {
        return BdBazar::select('id', 'bazar_name')
            ->where('ulb_id', $ulbId)
            ->orderBy('id', 'ASC')
            ->get();
    }
}

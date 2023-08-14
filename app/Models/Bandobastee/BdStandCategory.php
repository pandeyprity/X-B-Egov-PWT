<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdStandCategory extends Model
{
    use HasFactory;
    /**
     * | Get stand category List
     */
    public function listCategory()
    {
        return BdStandCategory::select('id', 'stand_category')
            ->where('status', '1')
            ->get();
    }
}

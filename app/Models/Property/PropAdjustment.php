<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropAdjustment extends Model
{
    use HasFactory;
    protected $guarded = [];


    /**
     * | Store new Adjustment
     */
    public function store($req)
    {
        PropAdjustment::create($req);
    }
}

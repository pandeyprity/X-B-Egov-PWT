<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropBuildingRentalconst extends Model
{
    use HasFactory;

    protected  $guarded = [];

    // public $timestamps = false;
    
    protected $hidden = ['created_at', 'updated_at'];
    
    public function show(array $req){
        MPropBuildingRentalconst::view($req);
    }


}    




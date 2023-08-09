<?php

namespace App\Models;

use App\Models\PropApartmentDtl as ModelsPropApartmentDtl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropApartmentDtl extends Model
{
    use HasFactory;
    
    protected  $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];
    
    public function show(array $req){
        PropApartmentDtl::view($req);
    }
}

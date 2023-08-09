<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropGbbuildingusagetype extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req){
        RefPropGbbuildingusagetype::view($req);
    }
}

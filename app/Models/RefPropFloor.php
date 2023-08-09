<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropFloor extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req){
        RefPropFloor::view($req);
    }
}

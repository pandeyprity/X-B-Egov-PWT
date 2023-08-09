<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropObjectionType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at', 'delated_at'];

    public function show(array $req){
       RefPropObjectionType::view($req);
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WardList extends Model
{
    use HasFactory;
    // use SoftDeletes;
    protected $table ='ulb_ward_masters';

    // protected $dates = ['deleted_at'];

    protected $guarded = [];

    public function store(array $req){
        WardList::create($req);
    }

    public function show(array $req){
        WardList::view($req);
    }

    public function edit(array $req){
        WardList::update($req);
    }

    public function deactivated(array $req){
        WardList::update($req);
    }
}

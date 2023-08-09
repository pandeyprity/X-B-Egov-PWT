<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropTranDtl extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Store Prop Tran Dtls
    public function store(array $req)
    {
        PropTranDtl::create($req);
    }
}

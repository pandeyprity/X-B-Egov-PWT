<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPartPaymentDocument extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_water";

    public function postDocuments($req)
    {
       return WaterPartPaymentDocument::create($req);
    }
}

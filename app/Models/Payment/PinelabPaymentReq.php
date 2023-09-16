<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinelabPaymentReq extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = 'pgsql_master';

    public function store($req)
    {
        $data =  PinelabPaymentReq::create($req);
        return $data;
    }
}

<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IciciPaymentReq extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';
    protected $guarded = [];
}

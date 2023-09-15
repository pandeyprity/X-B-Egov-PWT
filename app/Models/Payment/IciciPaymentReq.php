<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IciciPaymentReq extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';
    protected $guarded = [];

    /**
     * | find reqRefNo
     */
    public function findByReqRefNo($reqRefNo)
    {
        return self::where('req_ref_no', $reqRefNo)
            ->first();
    }

    /**
     * | Find with help of Refe No 
     */
    public function getByReqRefNo($reqRefNo)
    {
        return self::where('req_ref_no', $reqRefNo);
    }
    
}

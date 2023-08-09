<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReject extends Model
{
    use HasFactory;

    /**
     * |------------------------------ Save the rejected data with flag ---------------------------------|
     * | @param
     * | @var 
        | Serial No :
     */
    public function saveRejectedData($request)
    {
        $rejectData = new PaymentReject();
        $rejectData->razerpay_order_id      = $request->razorpayOrderId;
        $rejectData->razerpay_payment_id    = $request->razorpayPaymentId;
        $rejectData->razerpay_signature     = $request->razorpaySignature;
        $rejectData->reason                 = $request->reason;
        $rejectData->source                 = $request->source;
        $rejectData->step                   = $request->step;
        $rejectData->code                   = $request->code;
        $rejectData->description            = $request->description;
        if (!empty($request->razorpaySignature)) {
            $rejectData->suspecious = true;
        }
        $rejectData->save();
    }
}

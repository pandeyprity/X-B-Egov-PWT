<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSuccess extends Model
{
    use HasFactory;

    /**
     * |---------------------------------- Save Success data ----------------------------------------|
     * | @param 
     * | @var 
        | Serial No : 
     */
    public function saveSuccessDetails($request)
    {
        $successData = new PaymentSuccess();
        $successData->razerpay_order_id = $request->razorpayOrderId;
        $successData->razerpay_payment_id = $request->razorpayPaymentId;
        $successData->razerpay_signature = $request->razorpaySignature;
        $successData->save(); 
    }
}

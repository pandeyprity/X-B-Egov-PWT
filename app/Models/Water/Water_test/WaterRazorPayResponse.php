<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterRazorPayResponse extends Model
{
    use HasFactory;
    public $timestamps = false;


    /**
     * | Save data for the razorpay response
     */
    public function savePaymentResponse($RazorPayRequest, $webhookData)
    {
        $RazorPayResponse = new WaterRazorPayResponse();
        $RazorPayResponse->related_id   = $RazorPayRequest->related_id;
        $RazorPayResponse->request_id   = $RazorPayRequest->id;
        $RazorPayResponse->amount       = $webhookData['amount'];
        $RazorPayResponse->merchant_id  = $webhookData['merchantId'] ?? null;
        $RazorPayResponse->order_id     = $webhookData["orderId"];
        $RazorPayResponse->payment_id   = $webhookData["paymentId"];
        $RazorPayResponse->save();
        return [
            'razorpayResponseId' => $RazorPayResponse->id
        ];
    }
}

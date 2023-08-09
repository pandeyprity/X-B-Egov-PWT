<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterRazorPayRequest extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * | Save data for the payment requests
     * | @param
     */
    public function saveRequestData($request, $paymentFor, $temp, $refDetails)
    {
        $RazorPayRequest = new WaterRazorPayRequest;
        $RazorPayRequest->related_id        = $request->consumerId;
        $RazorPayRequest->payment_from      = $paymentFor;
        $RazorPayRequest->amount            = $request->amount;
        $RazorPayRequest->demand_from_upto  = $request->demandFrom . "--" . $request->demandUpto;
        $RazorPayRequest->ip_address        = $request->ip();
        $RazorPayRequest->order_id          = $temp["orderId"];
        $RazorPayRequest->department_id     = $temp["departmentId"];
        $RazorPayRequest->adjusted_amount   = $refDetails["adjustedAmount"];
        $RazorPayRequest->due_amount        = $refDetails["leftDemandAmount"]; # dont save 
        $RazorPayRequest->penalty_amount    = $refDetails["penaltyAmount"];
        $RazorPayRequest->remarks           = $request->remarks;
        $RazorPayRequest->save();
    }

    /**
     * | Get 
     */
    public function checkRequest($webhookData)
    {
        return WaterRazorPayRequest::select("*")
        ->where("order_id", $webhookData["orderId"])
        ->where("related_id", $webhookData["id"])
        ->where("status", 2);
    }
}

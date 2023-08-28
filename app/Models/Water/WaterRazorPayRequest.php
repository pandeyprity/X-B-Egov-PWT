<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterRazorPayRequest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection = 'pgsql_water';

    /**
     * | Save data for the payment requests
     * | @param
     */
    public function saveRequestData($request, $paymentFor, $temp, $refDetails)
    {
        $RazorPayRequest = new WaterRazorPayRequest;
        $RazorPayRequest->related_id        = $request->consumerId ?? $request->applicationId;
        $RazorPayRequest->payment_from      = $paymentFor;
        $RazorPayRequest->amount            = $request->amount ?? $refDetails['totalAmount'];
        $RazorPayRequest->demand_from_upto  = $request->demandFrom ? ($request->demandFrom . "--" . $request->demandUpto) : null;
        $RazorPayRequest->ip_address        = $request->ip();
        $RazorPayRequest->order_id          = $temp["orderId"];
        $RazorPayRequest->department_id     = $temp["departmentId"];
        $RazorPayRequest->adjusted_amount   = $refDetails["adjustedAmount"] ?? null;
        $RazorPayRequest->due_amount        = $refDetails["leftDemandAmount"] ?? null; # dont save 
        $RazorPayRequest->penalty_amount    = $refDetails["penaltyAmount"] ?? null;
        $RazorPayRequest->remarks           = $request->remarks;
        $RazorPayRequest->conumer_charge_id = $refDetails['chargeCatagoryId'] ?? null;
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

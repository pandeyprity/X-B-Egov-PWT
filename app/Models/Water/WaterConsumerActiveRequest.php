<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerActiveRequest extends Model
{
    use HasFactory;

    /**
     * | Save request details 
     */
    public function saveRequestDetails($req, $consumerDetails, $refRequest, $applicationNo)
    {
        $mWaterConsumerActiveRequest = new WaterConsumerActiveRequest();
        $mWaterConsumerActiveRequest->consumer_id           = $consumerDetails->id;
        $mWaterConsumerActiveRequest->apply_date            = Carbon::now();
        $mWaterConsumerActiveRequest->citizen_id            = $refRequest['citizenId'] ?? null;
        $mWaterConsumerActiveRequest->created_at            = Carbon::now();
        $mWaterConsumerActiveRequest->emp_details_id        = $refRequest['empId'] ?? null;
        $mWaterConsumerActiveRequest->ward_mstr_id          = $consumerDetails['ward_mstr_id'];
        $mWaterConsumerActiveRequest->reason                = $req['reason'];
        $mWaterConsumerActiveRequest->amount                = $refRequest['amount'];
        $mWaterConsumerActiveRequest->remarks               = $req['remarks'];
        $mWaterConsumerActiveRequest->apply_from            = $refRequest['applyFrom'];
        $mWaterConsumerActiveRequest->initiator             = $refRequest['initiatorRoleId'];
        $mWaterConsumerActiveRequest->workflow_id           = $refRequest['ulbWorkflowId'];
        $mWaterConsumerActiveRequest->ulb_id                = $req['ulbId'];
        $mWaterConsumerActiveRequest->finisher              = $refRequest['finisherRoleId'];
        $mWaterConsumerActiveRequest->user_type             = $refRequest['userType'];
        $mWaterConsumerActiveRequest->application_no        = $applicationNo;
        $mWaterConsumerActiveRequest->charge_catagory_id    = $refRequest['chargeCatagoryId'];
        $mWaterConsumerActiveRequest->save();
        return [
            "id" => $mWaterConsumerActiveRequest->id
        ];
    }

    /**
     * | Get Active appication by consumer Id
     */
    public function getRequestByConId($consumerId)
    {
        return WaterConsumerActiveRequest::where('consumer_id', $consumerId)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}

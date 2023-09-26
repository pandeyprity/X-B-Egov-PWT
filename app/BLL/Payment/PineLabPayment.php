<?php

namespace App\BLL\Payment;

use App\Models\Payment\PinelabPaymentReq;
use Illuminate\Support\Facades\Config;

/**
 * | Author - Anshu Kumar
 * | Created On-26-09-2023 
 * | Created for - PineLab Payment Functions
 * | Status-Closed
 */

class PineLabPayment
{
    /**
     * | Generate Order Id
     */
    protected function getBillRefNo(int $modeuleId)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        $orderId = (("Order_" . $modeuleId . date('dmyhism') . $randomString));
        $orderId = explode("=", chunk_split($orderId, 30, "="))[0];
        return $orderId;
    }

    /**
     * | Save Pine lab Request
     * | $validator = Validator::make($req->all(), [
     *       "workflowId"    => "nullable|int",
     *      "amount"        => "required|numeric",
     *      "moduleId"      => "nullable|int",
     *      "applicationId" => "required|int",
     * ]);
     */
    public function initiatePayment($req)
    {
        $mPinelabPaymentReq =  new PinelabPaymentReq();
        $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $moduleId = $req->moduleId;

        if ($req->paymentType == 'Property' || 'Saf')
            $moduleId = $propertyModuleId;

        $user = authUser($req);
        $mReqs = [
            "ref_no"          => $this->getBillRefNo($moduleId),
            "user_id"         => $user->id,
            "workflow_id"     => $req->workflowId,
            "amount"          => $req->amount,
            "module_id"       => $moduleId,
            "ulb_id"          => $req->ulbId ?? 2,
            "application_id"  => $req->applicationId,
            "payment_type"    => $req->paymentType

        ];
        $data = $mPinelabPaymentReq->store($mReqs);
        return $data->ref_no;

        // return responseMsgs(true, "Bill id is", ['billRefNo' => $data->ref_no], "", 01, responseTime(), $req->getMethod(), $req->deviceId);
    }
}

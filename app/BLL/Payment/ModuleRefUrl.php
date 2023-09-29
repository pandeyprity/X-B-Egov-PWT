<?php

namespace App\BLL\Payment;

use App\Models\Payment\IciciPaymentReq;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

/**
 * | Author - Anshu Kumar
 * | Created On-12-09-2023 
 * | Created for - Module Specifil Referal Urls
 * | Status-Closed
 */
class ModuleRefUrl extends GetRefUrl
{
    // Generation of Referal url for payment for Testing
    public function getReferalUrl(Request $req)
    {
        $mIciciPaymentReq = new IciciPaymentReq();          // We Have to pass transaction amount here which is pending
        $this->_tranAmt = $req->amount;
        $url = $this->generateRefUrl();
        $paymentReq = [
            "user_id" => isEmpty($req->userId) ? null : $req->userId,
            "workflow_id" => $req->workflowId,
            "req_ref_no" => $this->_refNo,
            "amount" => $req->amount,
            "application_id" => $req->applicationId,
            "module_id" => $req->moduleId,
            "ulb_id" => $req->ulbId ?? 2,
            "referal_url" => $url['encryptUrl']
        ];
        $mIciciPaymentReq->create($paymentReq);
    }
}

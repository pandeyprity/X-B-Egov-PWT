<?php

namespace App\BLL\Market;

use App\Models\Rentals\ShopPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-14-06-2023 
 * | Author-Anshu Kumar
 * | Status-Open
 */
class ShopPaymentBll
{
    private $_mShopPayments;
    public $_shopDetails;
    public $_tranId;

    public function __construct()
    {
        $this->_mShopPayments = new ShopPayment();
    }

    /**
     * | Shop Payments
     * | @param Request $req
     */
    public function shopPayment($req)
    {
        // Business Logics
        $paymentTo = Carbon::parse($req->paymentTo);
        if (!isset($this->_tranId))                                 // If Last Transaction Not Found
        {
            $paymentFrom = Carbon::parse($req->paymentFrom);
            $diffInMonths = $paymentFrom->diffInMonths($paymentTo);
            $totalMonths = $diffInMonths + 1;
        }

        if (isset($this->_tranId)) {                                // If Last Transaction ID is Available
            $shopLastPayment = $this->_mShopPayments::findOrFail($this->_tranId);
            $paymentFrom = Carbon::parse($shopLastPayment->paid_to);
            $diffInMonths = $paymentFrom->diffInMonths($paymentTo);
            $totalMonths = $diffInMonths + 1;
        }

        $payableAmt = ($this->_shopDetails->rate * $totalMonths) + $this->_shopDetails->arrear;
        $amount = $req->amount;
        $arrear = $payableAmt - $amount;
        if ($payableAmt < 1)
            throw new Exception("Dues Not Available");
        // Insert Payment 
        $paymentReqs = [
            'shop_id' => $req->shopId,
            'paid_from' => $paymentFrom,
            'paid_to' => $paymentTo,
            'demand' => $payableAmt,
            'amount' => $amount,
            'rate' => $this->_shopDetails->rate,
            'months' => $totalMonths,
            'payment_date' => Carbon::now(),
            'user_id' => $req->auth['id'] ?? 0,
            'ulb_id' => $this->_shopDetails->ulb_id,
            'remarks' => $req->remarks
        ];
        DB::beginTransaction();
        $createdPayment = $this->_mShopPayments::create($paymentReqs);
        $this->_shopDetails->update([
            'last_tran_id' => $createdPayment->id,
            'arrear' => $arrear
        ]);
    }
}

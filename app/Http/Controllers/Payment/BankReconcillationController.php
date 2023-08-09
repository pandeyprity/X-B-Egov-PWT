<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeTransaction;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-14-02-2023 
 * | Created by-Mrinal Kumar
 * | Bank Reconcillation
 */

class BankReconcillationController extends Controller
{
    /**
     * |
     */
    public function searchTransaction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fromDate' => 'required',
                'toDate' => 'required',
                'moduleId' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => False, 'msg' => $validator()->errors()]);
            }
            $ulbId = authUser($request)->ulb_id;
            $moduleId = $request->moduleId;
            $paymentMode = $request->paymentMode;
            $verifyStatus = $request->verificationType;
            $fromDate = Carbon::create($request->fromDate)->format('Y-m-d');
            $toDate = Carbon::create($request->toDate)->format('Y-m-d');
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPropTransaction = new PropTransaction();
            $mTradeTransaction = new TradeTransaction();
            $mWaterTran = new WaterTran();

            if ($moduleId == $propertyModuleId) {
                $chequeTranDtl  = $mPropTransaction->chequeTranDtl($ulbId);

                if ($request->chequeNo) {
                    $data =  $chequeTranDtl
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
                if (!isset($data)) {
                    $data = $chequeTranDtl
                        ->whereBetween('tran_date', [$fromDate, $toDate])
                        ->get();
                }
            }

            if ($moduleId == $waterModuleId) {

                $chequeTranDtl  = $mWaterTran->chequeTranDtl($ulbId);

                if ($request->chequeNo) {
                    $data =  $chequeTranDtl
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
                if (!isset($data)) {
                    $data = $chequeTranDtl
                        ->whereBetween('tran_date', [$fromDate, $toDate])
                        ->get();
                }
            }

            if ($moduleId == $tradeModuleId) {
                $chequeTranDtl  = $mTradeTransaction->chequeTranDtl($ulbId);

                if ($request->chequeNo) {
                    $data =  $chequeTranDtl
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
                if (!isset($data)) {
                    $data = $chequeTranDtl
                        ->whereBetween('tran_date', [$fromDate, $toDate])
                        ->get();
                }
            }

            if ($paymentMode == 'DD') {
                $a =  collect($data)->where('payment_mode', 'DD');
                $data = (array_values(objtoarray($a)));
            }

            if ($paymentMode == 'CHEQUE') {
                $a =  collect($data)->where('payment_mode', 'CHEQUE');
                $data = (array_values(objtoarray($a)));
            }

            //search with verification status
            if ($verifyStatus == 'pending') {
                $a =  collect($data)->where('status', '2');
                $data = (array_values(objtoarray($a)));
            }

            if ($verifyStatus == 'clear') {
                $a =  collect($data)->where('status', '1');
                $data = (array_values(objtoarray($a)));
            }

            if ($verifyStatus == 'bounce') {
                $a =  collect($data)->where('status', '3');
                $data = (array_values(objtoarray($a)));
            }

            if (collect($data)->isNotEmpty()) {
                return responseMsgs(true, "Data Acording to request!", $data, '010801', '01', '382ms-547ms', 'Post', '');
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |
     */
    public function chequeDtlById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'moduleId' => 'required',
                'chequeId' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => False, 'msg' => $validator()->errors()]);
            }

            $moduleId = $request->moduleId;
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPropChequeDtl = new PropChequeDtl();
            $mTradeChequeDtl = new TradeChequeDtl();
            $mWaterChequeDtl = new WaterChequeDtl();


            switch ($moduleId) {
                    //Property
                case ($propertyModuleId):
                    $data = $mPropChequeDtl->chequeDtlById($request);
                    break;

                    //Water
                case ($waterModuleId):
                    $data = $mWaterChequeDtl->chequeDtlById($request);
                    break;

                    //Trade
                case ($tradeModuleId):
                    $data = $mTradeChequeDtl->chequeDtlById($request);
                    break;
            }

            if ($data)
                return responseMsg(true, "data found", $data);
            else
                return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |
     */
    public function chequeClearance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'moduleId' => 'required',
                'chequeId' => 'required',
                'status' => 'required|in:clear,bounce',
                'clearanceDate' => 'required'
            ]);

            if ($validator->fails()) {
                return validationError($validator);
            }
            $user = authUser($request);
            $ulbId = $user->ulb_id;
            $userId = $user->id;
            $moduleId = $request->moduleId;
            $paymentStatus = 1;
            $applicationPaymentStatus = 1;
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPaymentReconciliation = new PaymentReconciliation();

            if ($request->status == 'clear') {
                $applicationPaymentStatus = $paymentStatus = 1;
            }
            if ($request->status == 'bounce') {
                $paymentStatus = 3;
                $applicationPaymentStatus = 0;
            }

            DB::beginTransaction();

            if ($moduleId == $propertyModuleId) {
                $mChequeDtl =  PropChequeDtl::find($request->chequeId);

                $mChequeDtl->status = $paymentStatus;
                $mChequeDtl->clear_bounce_date = $request->clearanceDate;
                $mChequeDtl->bounce_amount = $request->cancellationCharge;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();

                $transaction = PropTransaction::where('id', $mChequeDtl->transaction_id)
                    ->first();
                $propId = $transaction->property_id;
                $safId = $transaction->saf_id;

                if ($propId)
                    $wardId = PropProperty::findorFail($propId)->ward_mstr_id;

                if ($safId)
                    $wardId = PropActiveSaf::findorFail($safId)->ward_mstr_id;

                PropTransaction::where('id', $mChequeDtl->transaction_id)
                    ->update(
                        [
                            'verify_status' => $paymentStatus,
                            'verify_date' => Carbon::now(),
                            'verified_by' => $userId
                        ]
                    );
                if ($safId)
                    PropActiveSaf::where('id', $safId)
                        ->update(
                            ['payment_status' => $applicationPaymentStatus]
                        );

                if ($applicationPaymentStatus == 0) {

                    $propTranDtls = PropTranDtl::where('tran_id', $transaction->id)->get();

                    foreach ($propTranDtls as $propTranDtl) {
                        $propDemandId = $propTranDtl->prop_demand_id;
                        $safDemandId = $propTranDtl->saf_demand_id;

                        if ($safDemandId) {
                            $safDemandDtl =  PropSafsDemand::where('id', $safDemandId)->first();
                            PropSafsDemand::where('id', $safDemandId)
                                ->update(
                                    [
                                        'paid_status' => $applicationPaymentStatus,
                                        'balance' => $safDemandDtl->amount - $safDemandDtl->adjust_amount,
                                    ]
                                );
                        }

                        if ($propDemandId) {
                            $propDemandDtl =  PropDemand::where('id', $propDemandId)->first();
                            PropDemand::where('id', $propDemandId)
                                ->update(
                                    [
                                        'paid_status' => $applicationPaymentStatus,
                                        'balance' => $propDemandDtl->amount - $propDemandDtl->adjust_amt,
                                    ]
                                );
                        }
                    }
                }

                $request->merge([
                    'id' => $mChequeDtl->id,
                    'paymentMode' => $transaction->payment_mode,
                    'transactionNo' => $transaction->tran_no,
                    'transactionAmount' => $transaction->amount,
                    'transactionDate' => $transaction->tran_date,
                    'wardId' => $wardId,
                    'chequeNo' => $mChequeDtl->cheque_no,
                    'branchName' => $mChequeDtl->branch_name,
                    'bankName' => $mChequeDtl->bank_name,
                    'clearanceDate' => $mChequeDtl->clear_bounce_date,
                    'bounceReason' => $mChequeDtl->remarks,
                    'chequeDate' => $mChequeDtl->cheque_date,
                    'moduleId' => $propertyModuleId,
                    'ulbId' => $ulbId,
                    'userId' => $userId,
                ]);

                // return $request;
                $mPaymentReconciliation->addReconcilation($request);
            }

            if ($moduleId == $waterModuleId) {
                $mChequeDtl =  WaterChequeDtl::find($request->chequeId);

                $mChequeDtl->status = $paymentStatus;
                $mChequeDtl->clear_bounce_date = $request->clearanceDate;
                $mChequeDtl->bounce_amount = $request->cancellationCharge;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();

                $transaction = WaterTran::where('id', $mChequeDtl->transaction_id)
                    ->first();
                $wardId = WaterApplication::find($transaction->related_id)->ward_id;

                WaterTran::where('id', $mChequeDtl->transaction_id)
                    ->update(
                        [
                            'verify_status' => $paymentStatus,
                            'verified_date' => Carbon::now(),
                            'verified_by' => $userId
                        ]
                    );

                WaterApplication::where('id', $mChequeDtl->application_id)
                    ->update(
                        [
                            'payment_status' => $applicationPaymentStatus
                        ]
                    );


                if ($paymentStatus == 3) {

                    $waterTranDtls = WaterTranDetail::where('tran_id', $transaction->id)->first();
                    $demandId = $waterTranDtls->demand_id;

                    if ($transaction->tran_type == 'Demand Collection') {
                        WaterConsumerDemand::where('id', $demandId)
                            ->update(
                                [
                                    'paid_status' => $applicationPaymentStatus
                                ]
                            );

                        $wardId = WaterConsumer::find($transaction->related_id)->ward_mstr_id;
                    }

                    if ($transaction->tran_type != 'Demand Collection') {
                        $connectionChargeDtl =  WaterConnectionCharge::find($demandId);
                        WaterConnectionCharge::where('id', $demandId)
                            ->update(
                                [
                                    'paid_status' => $applicationPaymentStatus
                                ]
                            );

                        WaterApplication::where('id', $connectionChargeDtl->application_id)
                            ->update(
                                [
                                    'payment_status' => $applicationPaymentStatus,

                                ]
                            );

                        //after penalty resolved
                        WaterPenaltyInstallment::where('related_demand_id', $demandId)
                            ->update(
                                [
                                    'paid_status' => $applicationPaymentStatus
                                ]
                            );
                    }
                }

                $request->merge([
                    'id' => $mChequeDtl->id,
                    'paymentMode' => $transaction->payment_mode,
                    'transactionNo' => $transaction->tran_no,
                    'transactionAmount' => $transaction->amount,
                    'transactionDate' => $transaction->tran_date,
                    'wardId' => $wardId,
                    'chequeNo' => $mChequeDtl->cheque_no,
                    'branchName' => $mChequeDtl->branch_name,
                    'bankName' => $mChequeDtl->bank_name,
                    'clearanceDate' => $mChequeDtl->clear_bounce_date,
                    'bounceReason' => $mChequeDtl->remarks,
                    'chequeDate' => $mChequeDtl->cheque_date,
                    'moduleId' => $waterModuleId,
                    'ulbId' => $ulbId,
                    'userId' => $userId,
                ]);

                // return $request;
                $mPaymentReconciliation->addReconcilation($request);
            }

            if ($moduleId == $tradeModuleId) {
                $mChequeDtl =  TradeChequeDtl::find($request->chequeId);

                $mChequeDtl->status = $paymentStatus;
                $mChequeDtl->clear_bounce_date = $request->clearanceDate;
                $mChequeDtl->bounce_amount = $request->cancellationCharge;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();

                $transaction = TradeTransaction::where('id', $mChequeDtl->tran_id)
                    ->first();

                TradeTransaction::where('id', $mChequeDtl->tran_id)
                    ->update(
                        [
                            'is_verified' => 1,
                            'verify_date' => Carbon::now(),
                            'verify_by' => $userId,
                            'status' => $paymentStatus,
                        ]
                    );


                //  Update in trade applications
                ActiveTradeLicence::where('id', $transaction->temp_id)
                    ->update(
                        ['payment_status' => $applicationPaymentStatus]
                    );

                $wardId = ActiveTradeLicence::find($mChequeDtl->temp_id)->ward_id;

                $request->merge([
                    'id' => $mChequeDtl->id,
                    'paymentMode' => $transaction->payment_mode,
                    'transactionNo' => $transaction->tran_no,
                    'transactionAmount' => $transaction->paid_amount,
                    'transactionDate' => $transaction->tran_date,
                    'wardId' => $wardId,
                    'chequeNo' => $mChequeDtl->cheque_no,
                    'branchName' => $mChequeDtl->branch_name,
                    'bankName' => $mChequeDtl->bank_name,
                    'clearanceDate' => $mChequeDtl->clear_bounce_date,
                    'chequeDate' => $mChequeDtl->cheque_date,
                    'moduleId' => $tradeModuleId,
                    'ulbId' => $ulbId,
                    'userId' => $userId,
                ]);

                // return $request;
                $mPaymentReconciliation->addReconcilation($request);
            }
            DB::commit();
            return responseMsg(true, "Data Updated!", '');
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}

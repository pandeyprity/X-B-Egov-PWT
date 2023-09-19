<?php

namespace App\Http\Controllers\Property;

use App\BLL\Payment\ModuleRefUrl;
use App\BLL\Property\Akola\GeneratePaymentReceipt;
use App\BLL\Property\Akola\PostPropPayment;
use App\BLL\Property\Akola\RevCalculateByAmt;
use App\BLL\Property\PaymentReceiptHelper;
use App\BLL\Property\PostRazorPayPenaltyRebate;
use App\BLL\Property\YearlyDemandGeneration;
use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqPayment;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\Models\Cluster\Cluster;
use App\Models\Payment\TempTransaction;
use App\Models\Property\PropAdjustment;
use App\Models\Property\PropAdvance;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropIcicipayPayment;
use App\Models\Property\PropOwner;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropRazorpayPenalrebate;
use App\Models\Property\PropRazorpayRequest;
use App\Models\Property\PropRazorpayResponse;
use App\Models\Property\PropSaf;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\UlbMaster;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use App\Traits\Property\SafDetailsTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class HoldingTaxController extends Controller
{
    use SAF;
    use Razorpay;
    use SafDetailsTrait;
    protected $_propertyDetails;
    protected $_safRepo;
    protected $_holdingTaxInterest = 0;
    protected $_paramRentalRate;
    protected $_refParamRentalRate;
    protected $_carbon;
    /**
     * | Created On-19/01/2023 
     * | Created By-Anshu Kumar
     * | Created for Holding Property Tax Demand and Receipt Generation
     * | Status-Closed
     */

    public function __construct(iSafRepository $safRepo)
    {
        $this->_safRepo = $safRepo;
        $this->_carbon = Carbon::now();
    }
    /**
     * | Generate Holding Demand(1)
     */
    public function generateHoldingDemand(Request $req)
    {
        $req->validate([
            'propId' => 'required|numeric'
        ]);
        try {
            $yearlyDemandGeneration = new YearlyDemandGeneration;
            $responseDemand = $yearlyDemandGeneration->generateHoldingDemand($req);
            return responseMsgs(true, "Property Demand", remove_null($responseDemand), "011601", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), ['holdingNo' => $yearlyDemandGeneration->_propertyDetails['holding_no']], "011601", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read the Calculation From Date (1.1)
     */
    public function generateCalculationParams($propertyId, $propDetails)
    {
        $mPropDemand = new PropDemand();
        $mSafDemand = new PropSafsDemand();
        $safId = $this->_propertyDetails->saf_id;
        $todayDate = Carbon::now();
        $propDemand = $mPropDemand->readLastDemandDateByPropId($propertyId);
        if (!$propDemand) {
            $propDemand = $mSafDemand->readLastDemandDateBySafId($safId);
            if (!$propDemand)
                throw new Exception("Last Demand is Not Available for this Property");
        }
        $lastPayDate = $propDemand->due_date;
        if (Carbon::parse($lastPayDate) > $todayDate)
            throw new Exception("No Dues For This Property");
        $payFrom = Carbon::parse($lastPayDate)->addDay(1);
        $payFrom = $payFrom->format('Y-m-d');

        $realFloor = collect($propDetails['floor'])->map(function ($floor) use ($payFrom) {
            $floor['dateFrom'] = $payFrom;
            return $floor;
        });

        $propDetails['floor'] = $realFloor->toArray();
        return $propDetails;
    }

    /**
     * | Get Holding Dues( 2) 
     */
    public function getHoldingDues(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['propId' => 'required']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 200);
        }

        try {
            $mPropDemand = new PropDemand();
            $mPropProperty = new PropProperty();
            $demand = array();
            $revCalculateByAmt = new RevCalculateByAmt;
            $demandList = collect();
            // Get Property Details
            $propBasicDtls = $mPropProperty->getPropBasicDtls($req->propId);
            $arrear = $propBasicDtls->balance;

            $demandList = $mPropDemand->getDueDemandByPropId($req->propId);
            $demandList = collect($demandList);

            $stateTaxDtls = [                                       // For Calculation Ref of State tax perc 
                'alv' => $demandList->last()->alv ?? 0,
                'state_education_tax' => $demandList->last()->state_education_tax ?? 0,
                'professional_tax' => $demandList->last()->professional_tax ?? 0
            ];

            if ($arrear > 0) {                                      // Reverse Calculation
                $revCalculateByAmt->_stateTaxDtls = $stateTaxDtls;
                $revCalculateByAmt->calculateRev($arrear);
                $demandList = $demandList->merge([$revCalculateByAmt->_GRID]);
            }

            if (isset($req->isArrear) && $req->isArrear)                            // If Citizen wants to pay only arrear from Payment function
                $demandList = $demandList->where('is_arrear', true)->values();

            $paymentStatus = $demandList->isEmpty() ? 1 : 0;

            $grandTaxes = $this->sumTaxHelper($demandList);

            $demand['paymentStatus'] = $paymentStatus;
            $demand['demandList'] = $demandList;
            $currentDemandList = collect($demandList)->where('fyear', getFY())->values();
            $demand['currentDemandList'] = $this->sumTaxHelper($currentDemandList);
            $overDueDemandList = collect($demandList)->where('fyear', '!=', getFY())->values();
            $demand['overdueDemandList'] =  $this->sumTaxHelper($overDueDemandList);
            $demand['grandTaxes'] = $grandTaxes;
            $demand['currentDemand'] = $demandList->where('fyear', getFY())->first()['balance'] ?? 0;
            $demand['arrear'] = $arrear;
            $demand['payableAmt'] = round($grandTaxes['balance']);
            // 🔴🔴 Property Payment and demand adjustments with arrear is pending yet 🔴🔴
            $holdingType = $propBasicDtls->holding_type;
            $ownershipType = $propBasicDtls->ownership_type;
            $basicDtls = collect($propBasicDtls)->only([
                'id',
                'holding_no',
                'new_holding_no',
                'ward_no',
                'property_type',
                'zone_name',
                'is_mobile_tower',
                'is_hoarding_board',
                'is_petrol_pump',
                'is_water_harvesting',
                'ulb_id',
                'prop_address',
                'land_occupation_date',
                'citizen_id',
                'user_id'
            ]);
            $basicDtls['moduleId'] = 1;
            $basicDtls['workflowId'] = 0;
            $basicDtls["holding_type"] = $holdingType;
            $basicDtls["ownership_type"] = $ownershipType;

            $demand['basicDetails'] = $basicDtls;

            return responseMsgs(true, "Demand Details", remove_null($demand), "011602", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), ['basicDetails' => $basicDtls ?? []], "011602", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Sum Tax Helper(Branch function of get holding dues 2.1)
     */
    public function sumTaxHelper($demandList)
    {
        return $demandList->pipe(function ($item) {
            return [
                "general_tax" => roundFigure($item->sum('general_tax')),
                "road_tax" => roundFigure($item->sum('road_tax')),
                "firefighting_tax" => roundFigure($item->sum('firefighting_tax')),
                "education_tax" => roundFigure($item->sum('education_tax')),
                "water_tax" => roundFigure($item->sum('water_tax')),
                "cleanliness_tax" => roundFigure($item->sum('cleanliness_tax')),
                "sewarage_tax" => roundFigure($item->sum('sewarage_tax')),
                "tree_tax" => roundFigure($item->sum('tree_tax')),
                "professional_tax" => roundFigure($item->sum('professional_tax')),
                "tax1" => roundFigure($item->sum('tax1')),
                "tax2" => roundFigure($item->sum('tax2')),
                "tax3" => roundFigure($item->sum('tax3')),
                "state_education_tax" => roundFigure($item->sum('sp_education_tax')),
                "water_benefit" => roundFigure($item->sum('water_benefit')),
                "water_bill" => roundFigure($item->sum('water_bill')),
                "sp_water_cess" => roundFigure($item->sum('sp_water_cess')),
                "drain_cess" => roundFigure($item->sum('drain_cess')),
                "light_cess" => roundFigure($item->sum('light_cess')),
                "major_building" => roundFigure($item->sum('major_building')),
                "total_tax" => roundFigure($item->sum('total_tax')),
                "balance" => roundFigure($item->sum('balance')),
            ];
        });
    }

    /**
     * | Generate Referal Url
     */
    public function getReferalUrl(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['propId' => 'required|integer']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ]);
        }
        // Initializations
        $moduleRefUrl = new ModuleRefUrl;
        $mPropIciciPayPayments = new PropIcicipayPayment();
        try {
            $holdingDues = $this->getHoldingDues($req);

            if ($holdingDues->original['status'] == false)
                throw new Exception($holdingDues->original['message']);

            if ($holdingDues->original['data']['paymentStatus'])
                throw new Exception("Payment Already Done");
            $holdingDues = $holdingDues->original['data'];
            $payableAmount = $holdingDues['payableAmt'];
            $basicDetails = $holdingDues['basicDetails'];

            $refReq = new Request([
                'userId' => $basicDetails['citizen_id'] ?? $basicDetails['user_id'],
                'amount' => $payableAmount,
                'applicationId' => $req->propId,
                'moduleId' => 1                             // Property Module Id
            ]);

            DB::beginTransaction();
            DB::connection('pgsql_master')->beginTransaction();
            $moduleRefUrl->getReferalUrl($refReq);
            $refurl = $moduleRefUrl->_refUrl;
            // Table maintain for particular module 
            $propIciciReqs = [
                "req_ref_no" => $moduleRefUrl->_refNo,
                "prop_id" => $req->propId,
                "tran_type" => 'Property',
                "from_fyear" => collect($holdingDues['demandList'])->first()['fyear'],
                "to_fyear" => collect($holdingDues['demandList'])->last()['fyear'],
                "demand_amt" => $holdingDues['grandTaxes']['balance'],
                "ulb_id" => 2,
                "ip_address" => $req->ipAddress ?? getClientIpAddress(),
                "demand_list" => json_encode($holdingDues, true),
                "payable_amount" => $holdingDues['payableAmt'],
                "arrear_settled" => $holdingDues['arrear']
            ];
            $mPropIciciPayPayments->create($propIciciReqs);
            DB::commit();
            DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "", $refurl);
        } catch (Exception $e) {
            DB::rollBack();
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), []);
        }
    }


    /**
     * | Payment Holding (Case for Online Payment)
     */
    public function paymentHolding(ReqPayment $req)
    {
        try {
            $userId = $req['userId'];
            $tranBy = 'ONLINE';
            $mPropDemand = new PropDemand();
            $mPropTrans = new PropTransaction();
            $propId = $req['id'];
            $mPropAdjustment = new PropAdjustment();
            $mPropRazorPayRequest = new PropRazorpayRequest();
            $mPropRazorpayPenalRebates = new PropRazorpayPenalrebate();
            $mPropPenaltyRebates = new PropPenaltyrebate();
            $mPropRazorpayResponse = new PropRazorpayResponse();

            $propDetails = PropProperty::findOrFail($propId);
            $orderId = $req['orderId'];
            $paymentId = $req['paymentId'];
            $razorPayReqs = new Request([
                'orderId' => $orderId,
                'key' => 'prop_id',
                'keyId' => $propId
            ]);
            $propRazorPayRequest = $mPropRazorPayRequest->getRazorPayRequests($razorPayReqs);
            if (collect($propRazorPayRequest)->isEmpty())
                throw new Exception("No Order Request Found");

            if (!$userId)
                $userId = 0;                                                        // For Ghost User in case of online payment

            $tranNo = $req['transactionNo'];

            $demands = json_decode($propRazorPayRequest->demand_list, true);
            $amount = $propRazorPayRequest['amount'];
            $advanceAmt = $propRazorPayRequest['advance_amount'];
            if (collect($demands)->isEmpty())
                throw new Exception("No Dues For this Property");

            DB::beginTransaction();
            // Replication of Prop Transactions
            $tranReqs = [
                'property_id' => $req['id'],
                'tran_date' => $this->_carbon->format('Y-m-d'),
                'tran_no' => $tranNo,
                'payment_mode' => 'ONLINE',
                'amount' => $amount,
                'tran_date' => $this->_carbon->format('Y-m-d'),
                'verify_date' => $this->_carbon->format('Y-m-d'),
                'citizen_id' => $userId,
                'is_citizen' => true,
                'from_fyear' => $propRazorPayRequest->from_fyear,
                'to_fyear' => $propRazorPayRequest->to_fyear,
                'from_qtr' => $propRazorPayRequest->from_qtr,
                'to_qtr' => $propRazorPayRequest->to_qtr,
                'demand_amt' => $propRazorPayRequest->demand_amt,
                'ulb_id' => $propRazorPayRequest->ulb_id,
            ];

            $storedTransaction = $mPropTrans->storeTrans($tranReqs);
            $tranId = $storedTransaction['id'];

            $razorpayPenalRebates = $mPropRazorpayPenalRebates->getPenalRebatesByReqId($propRazorPayRequest->id);
            // Replication of Razorpay Penalty Rebates to Prop Penal Rebates
            foreach ($razorpayPenalRebates as $item) {
                $propPenaltyRebateReqs = [
                    'tran_id' => $tranId,
                    'head_name' => $item['head_name'],
                    'amount' => $item['amount'],
                    'is_rebate' => $item['is_rebate'],
                    'tran_date' => $this->_carbon->format('Y-m-d'),
                    'prop_id' => $req['id'],
                ];
                $mPropPenaltyRebates->postRebatePenalty($propPenaltyRebateReqs);
            }

            // Updation of Prop Razor pay Request
            $propRazorPayRequest->status = 1;
            $propRazorPayRequest->payment_id = $paymentId;
            $propRazorPayRequest->save();

            // Update Prop Razorpay Response
            $razorpayResponseReq = [
                'razorpay_request_id' => $propRazorPayRequest->id,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'prop_id' => $req['id'],
                'from_fyear' => $propRazorPayRequest->from_fyear,
                'from_qtr' => $propRazorPayRequest->from_qtr,
                'to_fyear' => $propRazorPayRequest->to_fyear,
                'to_qtr' => $propRazorPayRequest->to_qtr,
                'demand_amt' => $propRazorPayRequest->demand_amt,
                'ulb_id' => $propDetails->ulb_id,
                'ip_address' => getClientIpAddress(),
            ];
            $mPropRazorpayResponse->store($razorpayResponseReq);

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $propDemand = $mPropDemand->getDemandById($demand['id']);
                $propDemand->balance = 0;
                $propDemand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $propDemand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $tranId;
                $propTranDtl->prop_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->ulb_id = $propDetails->ulb_id;
                $propTranDtl->save();
            }
            // Advance Adjustment 
            if ($advanceAmt > 0) {
                $adjustReq = [
                    'prop_id' => $propId,
                    'tran_id' => $tranId,
                    'amount' => $advanceAmt
                ];
                if ($tranBy == 'Citizen')
                    $adjustReq = array_merge($adjustReq, ['citizen_id' => $userId ?? 0]);
                else
                    $adjustReq = array_merge($adjustReq, ['user_id' => $userId ?? 0]);

                $mPropAdjustment->store($adjustReq);
            }
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", ['TransactionNo' => $tranNo], "011604", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011604", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Offline Payment Holding for (Cheque, Cash, DD and Neft)
     */
    public function offlinePaymentHolding(ReqPayment $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['isArrear' => 'required|bool']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 401);
        }
        $postPropPayment = new PostPropPayment($req);
        $propCalReq = new Request([                                                 // Request from payment
            'propId' => $req['id'],
            'isArrear' => $req['isArrear'] ?? null
        ]);

        try {
            $propCalculation = $this->getHoldingDues($propCalReq);                    // Calculation of Holding
            if ($propCalculation->original['status'] == false)
                throw new Exception($propCalculation->original['message']);

            $postPropPayment->_propCalculation = $propCalculation;
            // Transaction is beginning in Prop Payment Class
            $postPropPayment->postPayment();
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", ['TransactionNo' => $postPropPayment->_tranNo], "011604", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011604", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $mTempTransaction = new TempTransaction();
        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new PropChequeDtl();
            $chequeReqs = [
                'user_id' => $req['userId'],
                'prop_id' => $req['id'],
                'transaction_id' => $req['tranId'],
                'cheque_date' => $req['chequeDate'],
                'bank_name' => $req['bankName'],
                'branch_name' => $req['branchName'],
                'cheque_no' => $req['chequeNo']
            ];
            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id' => $req['tranId'],
            'application_id' => $req['id'],
            'module_id' => $moduleId,
            'workflow_id' => 0,
            'transaction_no' => $req['tranNo'],
            'application_no' => $req->applicationNo,
            'amount' => $req['amount'],
            'payment_mode' => $req['paymentMode'],
            'cheque_dd_no' => $req['chequeNo'],
            'bank_name' => $req['bankName'],
            'tran_date' => $req['todayDate'],
            'user_id' => $req['userId'],
            'ulb_id' => $req['ulbId'],
            // 'cluster_id' => $clusterId
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }

    /**
     * | Legacy Payment Holding
     */
    public function legacyPaymentHolding(ReqPayment $req)
    {
        $req->validate([
            'document' => 'required|mimes:pdf,jpeg,png,jpg'
        ]);
        try {
            $mPropDemand = new PropDemand();
            $mPropProperty = new PropProperty();
            $propWfId = Config::get('workflow-constants.PROPERTY_WORKFLOW_ID');
            $docUpload = new DocUpload;
            $refImageName = "LEGACY_PAYMENT";
            $propModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $relativePath = Config::get('PropertyConstaint.SAF_RELATIVE_PATH');
            $mWfActiveDocument = new WfActiveDocument();

            $propCalReq = new Request([
                'propId' => $req['id'],
                'fYear' => $req['fYear'],
                'qtr' => $req['qtr']
            ]);

            $properties = $mPropProperty::findOrFail($req['id']);
            $propCalculation = $this->getHoldingDues($propCalReq);
            if ($propCalculation->original['status'] == false)
                throw new Exception($propCalculation->original['message']);

            // Image Upload
            $imageName = $docUpload->upload($refImageName, $req->document, $relativePath);
            $demands = $propCalculation->original['data']['demandList'];

            $wfActiveDocReqs = [
                'active_id' => $req['id'],
                'workflow_id' => $propWfId,
                'ulb_id' => $properties->ulb_id,
                'module_id' => $propModuleId,
                'doc_code' => $refImageName,
                'relative_path' => $relativePath,
                'document' => $imageName,
                'uploaded_by' => authUser($req)->id,
                'uploaded_by_type' => authUser($req)->user_type,
                'doc_category' => $refImageName,
            ];
            DB::beginTransaction();
            $mWfActiveDocument->create($wfActiveDocReqs);
            foreach ($demands as $demand) {
                $tblDemand = $mPropDemand->getDemandById($demand['id']);
                $tblDemand->paid_status = 9;
                $tblDemand->adjust_type = "Legacy Payment Adjustment";
                $tblDemand->save();
            }
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", ['TransactionNo' => ""], "011604", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011604", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Generate Payment Receipt(9.1)
     */
    public function propPaymentReceipt(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['tranNo' => 'required']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 401);
        }
        try {

            $generatePaymentReceipt = new GeneratePaymentReceipt;
            $generatePaymentReceipt->generateReceipt($req->tranNo);
            $receipt = $generatePaymentReceipt->_GRID;
            return responseMsgs(true, "Payment Receipt", remove_null($receipt), "011605", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011605", "1.0", "", "POST", $req->deviceId);
        }
    }

    /**
     * | Property Payment History
     */
    public function propPaymentHistory(Request $req)
    {
        $req->validate([
            'propId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $mPropTrans = new PropTransaction();
            $mPropProperty = new PropProperty();

            $transactions = array();

            $propertyDtls = $mPropProperty->getSafByPropId($req->propId);
            if (!$propertyDtls)
                throw new Exception("Property Not Found");

            $propTrans = $mPropTrans->getPropTransactions($req->propId, 'property_id');         // Holding Payment History
            if (!$propTrans || $propTrans->isEmpty())
                throw new Exception("No Transaction Found");

            $propTrans->map(function ($propTran) {
                $propTran['tran_date'] = Carbon::parse($propTran->tran_date)->format('d-m-Y');
            });

            $propSafId = $propertyDtls->saf_id;

            if (is_null($propSafId))
                $safTrans = array();
            else {
                $safTrans = $mPropTrans->getPropTransactions($propSafId, 'saf_id');                 // Saf payment History
                $safTrans->map(function ($safTran) {
                    $safTran['tran_date'] = Carbon::parse($safTran->tran_date)->format('d-m-Y');
                });
            }

            $transactions['Holding'] = collect($propTrans)->sortByDesc('id')->values();
            $transactions['Saf'] = collect($safTrans)->sortByDesc('id')->values();

            return responseMsgs(true, "", remove_null($transactions), "011606", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011606", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Generate Ulb Payment Receipt
     */
    public function proUlbReceipt(Request $req)
    {
        $req->validate([
            'tranNo' => 'required'
        ]);

        try {
            $mTransaction = new PropTransaction();
            $propTrans = $mTransaction->getPropTransFullDtlsByTranNo($req->tranNo);
            $responseData = $this->propPaymentReceipt($req);
            if ($responseData->original['status'] == false)
                return $responseData;
            $responseData = $responseData->original['data'];                                              // Function propPaymentReceipt(9.1)
            $totalRebate = $responseData['totalRebate'];
            $holdingTaxDetails = $this->holdingTaxDetails($propTrans, $totalRebate);                    // (9.2)
            $holdingTaxDetails = collect($holdingTaxDetails)->where('amount', '>', 0)->values();
            $responseData['holdingTaxDetails'] = $holdingTaxDetails;
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "011609", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011609", "1.0", "", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Holding Tax Details On RMC Receipt (9.2)
     */
    public function holdingTaxDetails($propTrans, $totalRebate)
    {
        $transactions = collect($propTrans);
        $tranDate = $transactions->first()->tran_date;
        $paidFinYear = calculateFYear($tranDate);
        $currentTaxes = $transactions->where('fyear', $paidFinYear)->values();
        $arrearTaxes = $transactions->where('fyear', '!=', $paidFinYear)->values();

        $arrearFromQtr = $arrearTaxes->first()->qtr ?? "";
        $arrearFromFyear = $arrearTaxes->first()->fyear ?? "";
        $arrearToQtr = $arrearTaxes->last()->qtr ?? "";
        $arrearToFyear = $arrearTaxes->last()->fyear ?? "";

        $currentFromQtr = $currentTaxes->first()->qtr ?? "";
        $currentFromFyear = $currentTaxes->first()->fyear ?? "";
        $currentToQtr = $currentTaxes->last()->qtr ?? "";
        $currentToFyear = $currentTaxes->last()->fyear ?? "";

        $arrearPeriod = $arrearFromQtr . '/' . $arrearFromFyear . '-' . $arrearToQtr . '/' . $arrearToFyear;
        $currentPeriod = $currentFromQtr . '/' . $currentFromFyear . '-' . $currentToQtr . '/' . $currentToFyear;
        return [
            [
                // 'codeOfAmount' => '1100100A',
                'description' => 'Holding Tax Arrear',
                'period' =>  $arrearPeriod,
                'amount' => roundFigure($arrearTaxes->sum('holding_tax')),
            ],
            [
                // 'codeOfAmount' => '1100100C',
                'description' => 'Holding Tax Current',
                'period' => $currentPeriod,
                'amount' => roundFigure($currentTaxes->sum('holding_tax')),
            ],
            [
                // 'codeOfAmount' => '1100200A',
                'description' => 'Water Tax Arrear',
                'period' =>  $arrearPeriod,
                'amount' =>  roundFigure($arrearTaxes->sum('water_tax')),
            ],
            [
                // 'codeOfAmount' => '1100200C',
                'description' => 'Water Tax Current',
                'period' => $currentPeriod,
                'amount' => roundFigure($currentTaxes->sum('water_tax')),
            ],
            [
                // 'codeOfAmount' => '1100400A',
                'description' => 'Conservancy Tax Arrear',
                'period' =>  $arrearPeriod,
                'amount' =>  roundFigure($arrearTaxes->sum('latrine_tax')),
            ],
            [
                // 'codeOfAmount' => '1100400C',
                'description' => 'Conservancy Tax Current',
                'period' => $currentPeriod,
                'amount' => roundFigure($currentTaxes->sum('latrine_tax')),
            ],
            [
                // 'codeOfAmount' => '1105201A',
                'description' => 'Education Cess Arrear',
                'period' =>  $arrearPeriod,
                'amount' =>  roundFigure($arrearTaxes->sum('education_cess')),
            ],
            [
                // 'codeOfAmount' => '1105201A',
                'description' => 'Education Cess Current',
                'period' => $currentPeriod,
                'amount' => roundFigure($currentTaxes->sum('education_cess')),
            ],
            [
                // 'codeOfAmount' => '1105203A',
                'description' => 'Health Cess Arrear',
                'period' =>   $arrearPeriod,
                'amount' => roundFigure($arrearTaxes->sum('health_cess')),
            ],
            [
                // 'codeOfAmount' => '1105203C',
                'description' => 'Health Cess Current',
                'period' => $currentPeriod,
                'amount' => roundFigure($currentTaxes->sum('health_cess')),
            ]
        ];
    }

    /**
     * | Property Comparative Demand(16)
     */
    public function comparativeDemand(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'propId' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return responseMsgs(false, $validator->errors(), "", "011610", "1.0", "", "POST", $req->deviceId ?? "");
        }

        try {
            // Variable Assignments
            $comparativeDemand = array();
            $comparativeDemand['arvRule'] = array();
            $comparativeDemand['cvRule'] = array();
            $propId = $req->propId;
            $safCalculation = new SafCalculation;
            $mPropProperty = new PropProperty();
            $floorTypes = Config::get('PropertyConstaint.FLOOR-TYPE');
            $effectDateRuleset2 = Config::get('PropertyConstaint.EFFECTIVE_DATE_RULE2');
            $effectDateRuleset3 = Config::get('PropertyConstaint.EFFECTIVE_DATE_RULE3');
            $mUlbMasters = new UlbMaster();
            // Derivative Assignments
            $fullDetails = $mPropProperty->getComparativeBasicDtls($propId);             // Full Details of the Floor
            $ulbId = $fullDetails[0]->ulb_id;
            if (collect($fullDetails)->isEmpty())
                throw new Exception("No Property Found");
            $basicDetails = collect($fullDetails)->first();
            $safCalculation->_redis = Redis::connection();
            $safCalculation->_rentalRates = $safCalculation->calculateRentalRates();
            $safCalculation->_effectiveDateRule2 = $effectDateRuleset2;
            $safCalculation->_effectiveDateRule3 = $effectDateRuleset3;
            $safCalculation->_multiFactors = $safCalculation->readMultiFactor();        // Get Multi Factors List
            $safCalculation->_propertyDetails['roadType'] = $basicDetails->road_width;
            $safCalculation->_propertyDetails['propertyType'] = $basicDetails->prop_type_mstr_id;
            $safCalculation->_readRoadType[$effectDateRuleset2] = $safCalculation->readRoadType($effectDateRuleset2);
            $safCalculation->_readRoadType[$effectDateRuleset3] = $safCalculation->readRoadType($effectDateRuleset3);
            $safCalculation->_ulbId = $basicDetails->ulb_id;
            $safCalculation->_wardNo = $basicDetails->old_ward_no;
            $safCalculation->readParamRentalRate();
            $safCalculation->_point20TaxedUsageTypes = Config::get('PropertyConstaint.POINT20-TAXED-COMM-USAGE-TYPES'); // The Type of Commercial Usage Types which have taxes 0.20 Perc


            if (!is_null($basicDetails->floor_id))                                          // If The Property Have Floors
            {
                $floors = array();
                foreach ($fullDetails as $detail) {
                    array_push($floors, [
                        'floorMstrId' => $detail->floor_mstr_id,
                        'buildupArea' => $detail->builtup_area,
                        'useType' => $detail->usage_type_mstr_id,
                        'constructionType' => $detail->const_type_mstr_id,
                        'carpetArea' => $detail->carpet_area,
                        'occupancyType' => $detail->occupancy_type_mstr_id,
                    ]);
                }
                $safCalculation->_floors = $floors;
                $capitalvalueRates = $safCalculation->readCapitalValueRate();
                foreach ($fullDetails as $key => $detail) {
                    $floorMstrId = $detail->floor_mstr_id;
                    $floorBuiltupArea = $detail->builtup_area;
                    $floorUsageType = $detail->usage_type_mstr_id;
                    $floorConstType = $detail->const_type_mstr_id;
                    $floorCarpetArea = $detail->carpet_area;
                    $floorFromDate = $detail->date_from;
                    $floorOccupancyType = $detail->occupancy_type_mstr_id;
                    $safCalculation->_floors[$floorMstrId]['useType'] = $floorUsageType;
                    $safCalculation->_floors[$floorMstrId]['buildupArea'] = $floorBuiltupArea;
                    $safCalculation->_floors[$floorMstrId]['carpetArea'] = $floorCarpetArea;
                    $safCalculation->_floors[$floorMstrId]['occupancyType'] = $floorOccupancyType;
                    $safCalculation->_floors[$floorMstrId]['constructionType'] = $floorConstType;
                    $safCalculation->_capitalValueRate[$floorMstrId] = $capitalvalueRates[$key];
                    $rules = $this->generateFloorComparativeDemand($floorFromDate, $floorTypes, $floorMstrId, $safCalculation);  // 16.1
                    array_push($comparativeDemand['arvRule'], $rules['arvRule']);
                    array_push($comparativeDemand['cvRule'], $rules['cvRule']);
                }
            }

            // Check Other Demands
            $otherDemands = $this->generateOtherDemands($basicDetails, $safCalculation);
            // Include other Demands
            $comparativeDemand['arvRule'] = array_merge($comparativeDemand['arvRule'], $otherDemands['arvRule']);
            $comparativeDemand['cvRule'] = array_merge($comparativeDemand['cvRule'], $otherDemands['cvRule']);

            $arvRule = $comparativeDemand['arvRule'];
            $cvRule = $comparativeDemand['cvRule'];
            $comparativeDemand['total'] = [
                'arvTotalPropTax' => roundFigure((float)collect($arvRule)->sum('arvTotalPropTax') ?? 0 + (float)collect($cvRule)->sum('arvTotalPropTax') ?? 0),
                'cvTotalPropTax' => roundFigure((float)collect($arvRule)->sum('cvArvPropTax') + (float)collect($cvRule)->sum('cvArvPropTax') ?? 0),
            ];
            $comparativeDemand['basicDetails'] = array_merge((array)$basicDetails, [
                'todayDate' => $this->_carbon->format('d-m-Y')
            ]);
            // Ulb Details
            $comparativeDemand['ulbDetails'] = $mUlbMasters->getUlbDetails($ulbId);
            return responseMsgs(true, "Comparative Demand", remove_null($comparativeDemand), "011610", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011610", "1.0", "", "POST", $req->deviceId);
        }
    }

    /**
     * | Generate Comparative Demand(16.1)
     */
    public function generateFloorComparativeDemand($floorFromDate, $floorTypes, $floorMstrId, $safCalculation, $onePercPenalty = 0)
    {
        if ($floorFromDate < $safCalculation->_effectiveDateRule3) {
            $rule2 = $safCalculation->calculateRuleSet2($floorMstrId, $onePercPenalty, $floorFromDate);
            $rule2 = array_merge(
                $rule2,
                ['circleRate' => ""],
                ['taxPerc' => ""],
                ['calculationFactor' => ""],
                ['matrixFactor' => $rule2['rentalRate']],
                ['cvArvPropTax' => 0],
                ['arvPsf' => $rule2['arv']],
                ['floorMstr' => $floorMstrId],
                ['floor' => $floorTypes[$floorMstrId]],
                ['ruleApplied' => 'Arv Rule']
            );
            $setRule2 = $this->responseDemand($rule2);          // Function (16.1)
        }

        $rule3 = $safCalculation->calculateRuleSet3($floorMstrId, $onePercPenalty, $floorFromDate);
        $rule3 = array_merge(
            $rule3,
            ['arvTotalPropTax' => 0],
            ['multiFactor' => ""],
            ['carpetArea' => ""],
            ['cvArvPropTax' => $rule3['arv']],
            ['arvPsf' => ""],
            ['floorMstr' => $floorMstrId],
            ['floor' => $floorTypes[$floorMstrId]],
            ['ruleApplied' => 'CV Rule'],
            ['rentalRate' => $rule3['matrixFactor']],       // Function (16.1)
        );
        $setRule3 = $this->responseDemand($rule3);
        return [
            'arvRule' => $setRule2 ?? [],
            'cvRule' => $setRule3 ?? []
        ];
    }

    /**
     * | response demands(16.1)
     */
    public function responseDemand($rule)
    {
        return [
            "floor" => $rule['floor'],
            "buildupArea" => $rule['buildupArea'],
            "usageFactor" => $rule['usageFactor'] ?? null,
            "occupancyFactor" => $rule['occupancyFactor'],
            "carpetArea" => $rule['carpetArea'] ?? null,
            "rentalRate" => $rule['rentalRate'],
            "taxPerc" => $rule['taxPerc'] ?? null,
            "calculationFactor" => $rule['calculationFactor'] ?? null,
            "arvPsf" => $rule['arvPsf'] ?? null,
            "circleRate" => $rule['circleRate'] ?? "",
            "arvTotalPropTax" => roundFigure($rule['arvTotalPropTax'] ?? 0),
            "cvArvPropTax" => roundFigure($rule['cvArvPropTax'] ?? 0)
        ];
    }

    /**
     * | Get Floor Demand (16.2)
     */
    public function generateOtherDemands($basicDetails, $safCalculation)
    {
        $onePercPenalty = 0;
        $array['arvRule'] = array();
        $array['cvRule'] = array();
        $safCalculation->_capitalValueRateMPH = $safCalculation->readCapitalValueRateMHP();
        // Mobile Tower
        if ($basicDetails->is_mobile_tower == true) {
            $safCalculation->_mobileTowerArea = $basicDetails->tower_area;
            if ($basicDetails->tower_installation_date < $safCalculation->_effectiveDateRule2) {
                $rule2 = $safCalculation->calculateRuleSet2("mobileTower", $onePercPenalty);
                $rule2['floor'] = "mobileTower";
                $rule2['usageFactor'] = $rule2['multiFactor'];
                $rule2['arvPsf'] = $rule2['arv'];
                array_push($array['arvRule'], $this->responseDemand($rule2)); // (16.1)
            }

            $rule3 = $safCalculation->calculateRuleSet3("mobileTower", $onePercPenalty);
            $rule3['floor'] = "mobileTower";
            $rule3['rentalRate'] = $rule3['matrixFactor'];
            $rule3['cvArvPropTax'] = $rule3['arv'];
            array_push($array['cvRule'], $this->responseDemand($rule3));
        }
        // Hoarding Board
        if ($basicDetails->is_hoarding_board == true) {
            $safCalculation->_hoardingBoard['area'] = $basicDetails->hoarding_area;
            if ($basicDetails->hoarding_installation_date < $safCalculation->_effectiveDateRule2) {
                $rule2 = $safCalculation->calculateRuleSet2("hoardingBoard", $onePercPenalty);
                $rule2['floor'] = "hoardingBoard";
                $rule2['usageFactor'] = $rule2['multiFactor'];
                $rule2['arvPsf'] = $rule2['arv'];
                array_push($array['arvRule'], $this->responseDemand($rule2)); // (16.1)
            }

            $rule3 = $safCalculation->calculateRuleSet3("hoardingBoard", $onePercPenalty);
            $rule3['floor'] = "hoardingBoard";
            $rule3['rentalRate'] = $rule3['matrixFactor'];
            $rule3['cvArvPropTax'] = $rule3['arv'];
            array_push($array['cvRule'], $this->responseDemand($rule3)); // (16.1)
        }
        // Petrol Pump
        if ($basicDetails->is_petrol_pump == true) {
            $safCalculation->_petrolPump['area'] = $basicDetails->under_ground_area;
            if ($basicDetails->petrol_pump_completion_date < $safCalculation->_effectiveDateRule2) {
                $rule2 = $safCalculation->calculateRuleSet2("petrolPump", $onePercPenalty);
                $rule2['floor'] = "petrolPump";
                $rule2['usageFactor'] = $rule2['multiFactor'];
                $rule2['arvPsf'] = $rule2['arv'];
                array_push($array['arvRule'], $this->responseDemand($rule2)); // (16.1)
            }

            $rule3 = $safCalculation->calculateRuleSet3("petrolPump", $onePercPenalty);
            $rule3['floor'] = "petrolPump";
            $rule3['rentalRate'] = $rule3['matrixFactor'];
            $rule3['cvArvPropTax'] = $rule3['arv'];
            array_push($array['cvRule'], $this->responseDemand($rule3)); // (16.1)
        }
        return $array;
    }

    /**
     * | Cluster Holding Dues
     */
    public function getClusterHoldingDues(Request $req)
    {
        $req->validate([
            'clusterId' => 'required|integer'
        ]);
        try {
            $todayDate = Carbon::now();
            $clusterId = $req->clusterId;
            $mPropProperty = new PropProperty();
            $mClusters = new Cluster();
            $penaltyRebateCalc = new PenaltyRebateCalculation;
            $mPropAdvance = new PropAdvance();
            $properties = $mPropProperty->getPropsByClusterId($clusterId);
            $clusterDemands = array();
            $finalClusterDemand = array();
            $clusterDemandList = array();
            $currentQuarter = calculateQtr($todayDate->format('Y-m-d'));
            $loggedInUserType = authUser($req)->user_type;
            $currentFYear = getFY();

            $clusterDtls = $mClusters::findOrFail($clusterId);

            if ($properties->isEmpty())
                throw new Exception("Properties Not Available");

            $arrear = $properties->sum('balance');
            foreach ($properties as $item) {
                $propIdReq = new Request([
                    'propId' => $item['id']
                ]);
                $demandList = $this->getHoldingDues($propIdReq)->original['data'];
                $propDues['duesList'] = $demandList['duesList'] ?? [];
                $propDues['demandList'] = $demandList['demandList'] ?? [];
                array_push($clusterDemandList, $propDues['demandList']);
                array_push($clusterDemands, $propDues);
            }
            $collapsedDemand = collect($clusterDemandList)->collapse();                       // Clusters Demands Collapsed into One

            if (collect($collapsedDemand)->isEmpty())
                throw new Exception("Demand Not Available For This Cluster");

            $groupedByYear = $collapsedDemand->groupBy('quarteryear');                        // Grouped By Financial Year and Quarter for the Separation of Demand  

            $summedDemand = $groupedByYear->map(function ($item) use ($penaltyRebateCalc) {                            // Sum of all the Demands of Quarter and Financial Year
                $quarterDueDate = $item->first()['due_date'];
                $onePercPenaltyPerc = $penaltyRebateCalc->calcOnePercPenalty($quarterDueDate);
                $balance = roundFigure($item->sum('balance'));

                $onePercPenaltyTax = ($balance * $onePercPenaltyPerc) / 100;
                $onePercPenaltyTax = roundFigure($onePercPenaltyTax);

                return [
                    'quarterYear' => $item->first()['quarteryear'],
                    'arv' => roundFigure($item->sum('arv')),
                    'qtr' => $item->first()['qtr'],
                    'holding_tax' => roundFigure($item->sum('holding_tax')),
                    'water_tax' => roundFigure($item->sum('water_tax')),
                    'education_cess' => roundFigure($item->sum('education_cess')),
                    'health_cess' => roundFigure($item->sum('health_cess')),
                    'latrine_tax' => roundFigure($item->sum('latrine_tax')),
                    'additional_tax' => roundFigure($item->sum('additional_tax')),
                    'amount' => roundFigure($item->sum('amount')),
                    'balance' => $balance,
                    'fyear' => $item->first()['fyear'],
                    'adjust_amt' => roundFigure($item->sum('adjust_amt')),
                    'due_date' => $quarterDueDate,
                    'onePercPenalty' => $onePercPenaltyPerc,
                    'onePercPenaltyTax' => $onePercPenaltyTax,
                ];
            })->values();

            $finalDues = collect($summedDemand)->sum('balance');
            $finalDues = roundFigure($finalDues);

            $finalOnePerc = collect($summedDemand)->sum('onePercPenaltyTax');
            $finalOnePerc = roundFigure($finalOnePerc);

            $finalAmt = $finalDues + $finalOnePerc + $arrear;
            $duesFrom = collect($clusterDemands)->first()['duesList']['duesFrom'] ?? collect($clusterDemands)->last()['duesList']['duesFrom'];
            $duesTo = collect($clusterDemands)->first()['duesList']['duesTo'] ?? collect($clusterDemands)->last()['duesList']['duesTo'];
            $paymentUptoYrs = collect($clusterDemands)->first()['duesList']['paymentUptoYrs'] ?? collect($clusterDemands)->last()['duesList']['paymentUptoYrs'];
            $paymentUptoQtrs = collect($clusterDemands)->first()['duesList']['paymentUptoQtrs'] ?? collect($clusterDemands)->last()['duesList']['paymentUptoQtrs'];

            $advanceAdjustments = $mPropAdvance->getClusterAdvanceAdjustAmt($clusterId);

            if (collect($advanceAdjustments)->isEmpty())
                $advanceAmt = 0;
            else
                $advanceAmt = $advanceAdjustments->advance - $advanceAdjustments->adjustment_amt;

            $advanceAmt = roundFigure($advanceAmt);
            $finalClusterDemand['duesList'] = [
                'paymentUptoYrs' => $paymentUptoYrs,
                'paymentUptoQtrs' => $paymentUptoQtrs,
                'duesFrom' => $duesFrom,
                'duesTo' => $duesTo,
                'totalDues' => $finalDues,
                'onePercPenalty' => $finalOnePerc,
                'finalAmt' => $finalAmt,
                'arrear' => $arrear,
                'advanceAmt' => $advanceAmt
            ];
            $mLastQuarterDemand = collect($summedDemand)->where('fyear', $currentFYear)->sum('balance');
            $finalClusterDemand['duesList'] = $penaltyRebateCalc->readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, null, $finalAmt, $finalClusterDemand['duesList']);
            $payableAmount = $finalAmt - ($finalClusterDemand['duesList']['rebateAmt'] + $finalClusterDemand['duesList']['specialRebateAmt']);
            $finalClusterDemand['duesList']['payableAmount'] = round($payableAmount - $advanceAmt);

            $finalClusterDemand['demandList'] = $summedDemand;
            $finalClusterDemand['basicDetails'] = $clusterDtls;
            return responseMsgs(true, "Generated Demand of the Cluster", remove_null($finalClusterDemand), "011611", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), ['basicDetails' => $clusterDtls], "011611", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Cluster Property Payments
     */
    public function clusterPayment(ReqPayment $req)
    {
        try {
            $dueReq = new Request([
                'clusterId' => $req->id
            ]);
            $clusterId = $req->id;
            $todayDate = Carbon::now();
            $idGeneration = new IdGeneration;
            $mPropTrans = new PropTransaction();
            $mPropDemand = new PropDemand();
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $mPropAdjustment = new PropAdjustment();

            $dues = $this->getClusterHoldingDues($dueReq);

            if ($dues->original['status'] == false)
                throw new Exception($dues->original['message']);

            $dues = $dues->original['data'];
            $demands = $dues['demandList'];
            $tranNo = $idGeneration->generateTransactionNo($req['ulbId']);
            $payableAmount = $dues['duesList']['payableAmount'];
            $advanceAmt = $dues['duesList']['advanceAmt'];
            // Property Transactions
            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $userId = authUser($req)->id ?? null;
                if (!$userId)
                    throw new Exception("User Should Be Logged In");
                $tranBy = authUser($req)->user_type;
            }
            $req->merge([
                'userId' => $userId,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo' => $tranNo,
                'amount' => $payableAmount,
                'tranBy' => $tranBy,
                'clusterType' => "Property"
            ]);

            DB::beginTransaction();
            $propTrans = $mPropTrans->postClusterTransactions($req, $demands);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $propTrans['id']
                ]);
                $this->postOtherPaymentModes($req);
            }

            $clusterDemand = $mPropDemand->getDemandsByClusterId($clusterId);
            if ($clusterDemand->isEmpty())
                throw new Exception("Demand Not Available");
            // Reflect on Prop Tran Details
            foreach ($clusterDemand as $demand) {
                $propDemand = $mPropDemand->getDemandById($demand['id']);
                $propDemand->balance = 0;
                $propDemand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $propDemand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans['id'];
                $propTranDtl->prop_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->ulb_id = $req['ulbId'];
                $propTranDtl->save();
            }
            // Replication Prop Rebates Penalties
            $this->postPaymentPenaltyRebate($dues['duesList'], null, $propTrans['id'], $clusterId);

            if ($advanceAmt > 0) {
                $adjustReq = [
                    'cluster_id' => $clusterId,
                    'tran_id' => $propTrans['id'],
                    'amount' => $advanceAmt
                ];
                if ($tranBy == 'Citizen')
                    $adjustReq = array_merge($adjustReq, ['citizen_id' => $userId ?? 0]);
                else
                    $adjustReq = array_merge($adjustReq, ['user_id' => $userId ?? 0]);

                $mPropAdjustment->store($adjustReq);
            }
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", ["tranNo" => $tranNo], "011612", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011612", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Cluster Payment History
     */
    public function clusterPaymentHistory(Request $req)
    {
        $req->validate([
            'clusterId' => "required|numeric"
        ]);

        try {
            $clusterId = $req->clusterId;
            $mPropTrans = new PropTransaction();
            $transactions = $mPropTrans->getPropTransactions($clusterId, "cluster_id");
            if ($transactions->isEmpty())
                throw new Exception("No Transaction Found for this Cluster");
            $transactions = $transactions->groupBy('tran_type');
            return responseMsgs(true, "Cluster Transactions", remove_null($transactions), "011613", "1.0", "", "", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011613", "1.0", "", "", $req->deviceId ?? "");
        }
    }

    /**
     * | Generate Cluster Payment Receipt For cluster saf and Holding (011613)
     */
    public function clusterPaymentReceipt(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['tranNo' => 'required']
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 401);
        }
        try {
            $mTransaction = new PropTransaction();
            $mPropPenalties = new PropPenaltyrebate();
            $mClusters = new Cluster();
            $paymentReceiptHelper = new PaymentReceiptHelper;

            $mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
            $mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');

            $rebatePenalMstrs = collect(Config::get('PropertyConstaint.REBATE_PENAL_MASTERS'));
            $onePercKey = $rebatePenalMstrs->where('id', 1)->first()['value'];
            $specialRebateKey = $rebatePenalMstrs->where('id', 6)->first()['value'];
            $firstQtrKey = $rebatePenalMstrs->where('id', 2)->first()['value'];
            $onlineRebate = $rebatePenalMstrs->where('id', 3)->first()['value'];

            $propTrans = $mTransaction->getPropByTranPropId($req->tranNo);
            $clusterId = $propTrans->cluster_id;

            $propCluster = $mClusters->getClusterDtlsById($clusterId);

            // Get Property Penalty and Rebates
            $penalRebates = $mPropPenalties->getPropPenalRebateByTranId($propTrans->id);

            $onePercPenalty = collect($penalRebates)->where('head_name', $onePercKey)->first()->amount ?? 0;
            $rebate = collect($penalRebates)->where('head_name', 'Rebate')->first()->amount ?? "";
            $specialRebate = collect($penalRebates)->where('head_name', $specialRebateKey)->first()->amount ?? 0;
            $firstQtrRebate = collect($penalRebates)->where('head_name', $firstQtrKey)->first()->amount ?? 0;
            $jskOrOnlineRebate = collect($penalRebates)->where('head_name', $onlineRebate)->first()->amount ?? 0;
            $lateAssessmentPenalty = 0;

            $taxDetails = $paymentReceiptHelper->readPenalyPmtAmts($lateAssessmentPenalty, $onePercPenalty, $rebate, $specialRebate, $firstQtrRebate, $propTrans->amount, $jskOrOnlineRebate);
            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $propTrans->tran_date,
                "transactionNo" => $propTrans->tran_no,
                "transactionTime" => $propTrans->created_at->format('H:i:s'),
                "applicationNo" => "",
                "customerName" => $propCluster->authorized_person_name,
                "mobileNo" => $propCluster->mobile_no,
                "receiptWard" => $propCluster->old_ward,
                "address" => $propCluster->address,
                "paidFrom" => $propTrans->from_fyear,
                "paidFromQtr" => $propTrans->from_qtr,
                "paidUpto" => $propTrans->to_fyear,
                "paidUptoQtr" => $propTrans->to_qtr,
                "paymentMode" => $propTrans->payment_mode,
                "bankName" => $propTrans->bank_name,
                "branchName" => $propTrans->branch_name,
                "chequeNo" => $propTrans->cheque_no,
                "chequeDate" => $propTrans->cheque_date,
                "demandAmount" => $propTrans->demand_amt,
                "taxDetails" => $taxDetails,
                "ulbId" => $propCluster->ulb_id,
                "oldWardNo" => $propCluster->old_ward,
                "newWardNo" => $propCluster->new_ward,
                "towards" => $mTowards,
                "description" => [
                    "keyString" => "Holding Tax"
                ],
                "totalPaidAmount" => $propTrans->amount,
                "paidAmtInWords" => getIndianCurrency($propTrans->amount),
            ];
            return responseMsgs(true, "Cluster Payment Receipt", remove_null($responseData), "011613", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011613", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Get Property Dues
     */
    public function propertyDues(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            ['propId' => 'required']
        );
        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'validation error',
                'errors'  => $validator->errors()
            ]);
        }
        $demandDues = $this->getHoldingDues($req);
        if ($demandDues->original['status'] == false)
            return responseMsgs(false, "No Dues Available for this Property", "");
        $demandDues = $demandDues->original['data']['duesList'];
        $demandDetails = $this->generateDemandDues($demandDues);
        $dataRow['dataRow'] = $demandDetails;
        $dataRow['btnUrl'] = "/viewDemandHoldingProperty/" . $req->propId;
        $data['tableTop'] =  [
            'headerTitle' => 'Property Dues',
            'tableHead' => ["#", "Dues From", "Dues To", "Total Dues", "1 % Penalty", "Rebate Amt", "Payable Amount"],
            'tableData' => [$dataRow]
        ];
        return responseMsgs(true, "Demand Dues", remove_null($data), "", "1.0", responseTime(), "POST", $req->deviceId ?? "");
    }
}

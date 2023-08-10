<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropDemand;
use App\Models\Property\PropTransaction;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IReport;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Traits\Auth;
use App\Traits\Property\Report;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

#------------date 13/03/2023 -------------------------------------------------------------------------
#   Code By Sandeep Bara
#   Payment Mode Wise Collection Report

class ReportController extends Controller
{
    use Auth;
    use Report;

    private $Repository;
    private $_common;
    public function __construct(IReport $TradeRepository)
    {
        DB::enableQueryLog();
        $this->Repository = $TradeRepository;
        $this->_common = new CommonFunction();
    }

    public function collectionReport(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr1.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->collectionReport($request);
    }

    public function safCollection(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr2.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safCollection($request);
    }

    public function safPropIndividualDemandAndCollection(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "key" => "nullable|regex:/^[^<>{};:.,~!?@#$%^=&*\"]*$/i",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr3.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safPropIndividualDemandAndCollection($request);
    }

    public function levelwisependingform(Request $request)
    {
        $request->request->add(["metaData" => ["pr4.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelwisependingform($request);
    }

    public function levelformdetail(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "roleId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr4.2", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelformdetail($request);
    }

    public function levelUserPending(Request $request)
    {
        $request->validate(
            [
                "roleId" => "required|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr4.2.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->levelUserPending($request);
    }

    public function userWiseLevelPending(Request $request)
    {
        $request->validate(
            [
                "userId" => "required|digits_between:1,9223372036854775807",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr4.2.2", 1.1, null, $request->getMethod(), null,]]);

        $refUser        = Auth()->user();
        $refUserId      = $refUser->id;
        $ulbId          = $refUser->ulb_id;
        $safWorkFlow = Config::get('workflow-constants.SAF_WORKFLOW_ID');
        if ($request->ulbId) {
            $ulbId = $request->ulbId;
        }

        $respons =  $this->levelformdetail($request);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        $roles = ($this->_common->getUserRoll($request->userId, $ulbId, $safWorkFlow));
        $respons = json_decode(json_encode($respons), true);
        if ($respons["original"]["status"]) {
            $respons["original"]["data"]["data"] = collect($respons["original"]["data"]["data"])->map(function ($val) use ($roles) {
                $val["role_name"] = $roles->role_name ?? "";
                $val["role_id"] = $roles->role_id ?? 0;
                return $val;
            });
        }
        return responseMsgs($respons["original"]["status"], $respons["original"]["message"], $respons["original"]["data"], $apiId, $version, $queryRunTime, $action, $deviceId);
    }

    public function userWiseWardWireLevelPending(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "userId" => "required|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr4.2.1.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->userWiseWardWireLevelPending($request);
    }

    public function safSamFamGeotagging(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr5.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->safSamFamGeotagging($request);
    }

    public function PropPaymentModeWiseSummery(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "userId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr1.2", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropPaymentModeWiseSummery($request);
    }

    public function SafPaymentModeWiseSummery(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "userId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr2.2", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->SafPaymentModeWiseSummery($request);
    }

    public function PropDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr7.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropDCB($request);
    }

    public function PropWardWiseDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                // "page" => "nullable|digits_between:1,9223372036854775807",
                // "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr8.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropWardWiseDCB($request);
    }

    public function PropFineRebate(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr9.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropFineRebate($request);
    }

    public function PropDeactedList(Request $request)
    {
        $request->validate(
            [
                "fromDate" => "nullable|date|date_format:Y-m-d",
                "uptoDate" => "nullable|date|date_format:Y-m-d",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "page" => "nullable|digits_between:1,9223372036854775807",
                "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr10.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->PropDeactedList($request);
    }


    //========================================================================================================
    // Modification By : Mrinal Kumar
    // Date : 11-03-2023

    /**
     * | Ward wise holding report
     */
    public function wardWiseHoldingReport(Request $request)
    {
        $mPropDemand = new PropDemand();
        $wardMstrId = $request->wardMstrId;
        $ulbId = authUser()->ulb_id;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $start = Carbon::createFromDate($request->year, 4, 1);

        $fromDate = $start->format('Y-m-d');
        if ($currentMonth > 3) {
            $end = Carbon::createFromDate($currentYear + 1, 3, 31);
            $toDate = $end->format('Y-m-d');
        } else
            $toDate = ($currentYear . '-03-31');

        $mreq = new Request([
            "fromDate" => $fromDate,
            "toDate" => $toDate,
            "ulbId" => $ulbId,
            "wardMstrId" => $wardMstrId,
            "perPage" => $request->perPage
        ]);

        $data = $mPropDemand->wardWiseHolding($mreq);

        $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
        return responseMsgs(true, "Ward Wise Holding Data!", $data, 'pr6.1', '1.1', $queryRunTime, 'Post', '');
    }

    /**
     * | List of financial year
     */
    public function listFY(Request $request)
    {
        $currentYear = Carbon::now()->year;
        $financialYears = array();
        $currentYear = date('Y');

        for ($year = 2015; $year <= $currentYear; $year++) {
            $startOfYear = $year . '-04-01'; // Financial year starts on April 1st
            $endOfYear = ($year + 1) . '-03-31'; // Financial year ends on March 31st
            $financialYear = getFinancialYear($startOfYear, 2015); // Calculate financial year and add a label
            $financialYears[] = $financialYear;
        }
        return responseMsgs(true, "Financial Year List", $financialYears, 'pr11.1', '01', '382ms-547ms', 'Post', '');
    }

    /**
     * | Printing of bulk receipt
     */
    public function bulkReceipt(Request $req, iSafRepository $safRepo)
    {
        $req->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date',
            'tranType' => 'required|In:Property,Saf',
            'userId' => 'required|numeric',
        ]);
        try {
            $fromDate = $req->fromDate;
            $toDate = $req->toDate;
            $userId = $req->userId;
            $tranType = $req->tranType;
            $mpropTransaction = new PropTransaction();
            $holdingCotroller = new HoldingTaxController($safRepo);
            $activeSafController = new ActiveSafController($safRepo);
            $propReceipts = collect();
            $receipts = collect();

            $transaction = $mpropTransaction->tranDtl($userId, $fromDate, $toDate);

            if ($tranType == 'Property')
                $data = $transaction->whereNotNull('property_id')->get();

            if ($tranType == 'Saf')
                $data = $transaction->whereNotNull('saf_id')->get();

            // if ($data->isEmpty())
            //     throw new Exception('No Data Found');

            $tranNos = collect($data)->pluck('tran_no');

            foreach ($tranNos as $tranNo) {
                $mreq = new Request(
                    ["tranNo" => $tranNo]
                );
                if ($tranType == 'Property')
                    $data = $holdingCotroller->propPaymentReceipt($mreq);

                if ($tranType == 'Saf')
                    $data = $activeSafController->generatePaymentReceipt($mreq);

                $propReceipts->push($data);
            }

            foreach ($propReceipts as $propReceipt) {
                $receipt = $propReceipt->original['data'];
                $receipts->push($receipt);
            }

            $queryRunTime = (collect(DB::getQueryLog($data))->sum("time"));

            return responseMsgs(true, 'Bulk Receipt', remove_null($receipts), '010801', '01', $queryRunTime, 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | GbSafCollection
     */
    public function gbSafCollection(Request $req)
    {
        $req->validate(
            [
                "fromDate" => "required|date|date_format:Y-m-d",
                "uptoDate" => "required|date|date_format:Y-m-d",
            ]
        );
        try {
            $fromDate = $req->fromDate;
            $uptoDate = $req->uptoDate;
            $perPage = $req->perPage ?? 5;
            $tbl1 = 'prop_active_safs';
            $officerTbl1 = 'prop_active_safgbofficers';
            $tbl2 = 'prop_safs';
            $officerTbl2 = 'prop_gbofficers';

            $first_query =  $this->gbSafCollectionQuery($tbl1, $fromDate, $uptoDate, $officerTbl1);
            $gbsafCollection = $this->gbSafCollectionQuery($tbl2, $fromDate, $uptoDate, $officerTbl2)
                ->union($first_query);

            if ($req->wardId)
                $gbsafCollection = $gbsafCollection->where('ward_mstr_id', $req->wardId);

            if ($req->paymentMode)
                $gbsafCollection = $gbsafCollection->where('payment_mode', $req->paymentMode);

            $list = $gbsafCollection->paginate($perPage);
            return $list;
            // $page = $req->page && $req->page > 0 ? $req->page : 1;
            // $paginator = $gbsafCollection->paginate($perPage);
            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            // $list = [
            //     "perPage" => $perPage,
            //     "page" => $page,
            //     "items" => $items,
            //     "total" => $total,
            //     "numberOfPages" => $numberOfPages
            // ];

            return responseMsgs(true, "GB Saf Collection!", $list, 'pr12.1', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Ward wise Individual Property Demand
     */
    public function propIndividualDemandCollection(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr13.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->propIndividualDemandCollection($request);
    }


    /**
     * | GBSAF Ward wise Individual Demand
     */
    public function gbsafIndividualDemandCollection(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr14.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->gbsafIndividualDemandCollection($request);
    }

    /**
     * | Not paid from 2019-2017
     */
    public function notPaidFrom2016(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr15.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->notPaidFrom2016($request);
    }

    /**
     * | Not paid from 2019-2017
     */
    public function previousYearPaidButnotCurrentYear(Request $request)
    {
        $request->validate(
            [
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardMstrId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["pr16.1", 1.1, null, $request->getMethod(), null,]]);
        return $this->Repository->previousYearPaidButnotCurrentYear($request);
    }

    /**
     * | Dcb Pie Chart
     */
    public function dcbPieChart(Request $request)
    {
        return $this->Repository->dcbPieChart($request);
    }

    /**
     * | 
     */
    public function propSafCollection(Request $request)
    {
        $propCollection = null;
        $safCollection = null;
        $gbsafCollection = null;
        $proptotalData = 0;
        $proptotal = 0;
        $saftotal = 0;
        $gbsaftotal = 0;
        $saftotalData = 0;
        $gbsaftotalData = 0;
        $collectionTypes = $request->collectionType;
        $perPage = $request->perPage ?? 5;

        if ($request->user == 'tc') {
            $userId = authUser()->id;
            $request->merge(["userId" => $userId]);
        }

        foreach ($collectionTypes as $collectionType) {
            if ($collectionType == 'property') {
                $propCollection =   $this->collectionReport($request);
                $proptotal = $propCollection->original['data']['totalAmount'];
                $proptotalData = $propCollection->original['data']['total'];
                $propCollection = $propCollection->original['data']['data'];
            }

            if ($collectionType == 'saf') {
                $safCollection = $this->safCollection($request);
                $saftotal = $safCollection->original['data']['totalAmount'];
                $saftotalData = $safCollection->original['data']['total'];
                $safCollection = $safCollection->original['data']['data'];
            }

            if ($collectionType == 'gbsaf') {
                $gbsafCollection = $this->gbSafCollection($request);
                $gbsaftotalData = $gbsafCollection->toarray()['total'];
                $gbsafCollection = $gbsafCollection->toarray()['data'];
                $gbsaftotal = collect($gbsafCollection)->sum('amount');
            }
        }
        $currentPage = $request->page ?? 1;
        $details = collect($propCollection)->merge($safCollection)->merge($gbsafCollection);

        $a = round($proptotalData / $perPage);
        $b = round($saftotalData / $perPage);
        $c = round($gbsaftotalData / $perPage);
        $data['current_page'] = $currentPage;
        $data['total'] = $proptotalData + $saftotalData + $gbsaftotalData;
        $data['totalAmt'] = round($proptotal + $saftotal + $gbsaftotal);
        $data['last_page'] = max($a, $b, $c);
        $data['data'] = $details;

        return responseMsgs(true, "", $data, "", "", "", "post", $request->deviceId);
    }

    /**
     * | Holding Wise Rebate & Penalty
     */
    public function rebateNpenalty(Request $request)
    {
        return $this->Repository->rebateNpenalty($request);
    }
}

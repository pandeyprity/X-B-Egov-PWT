<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest\RequestCollectionPercentage;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterTran;
use App\Repository\Common\CommonFunction;
use App\Repository\Dashboard\IStateDashboard;
use App\Repository\Dashboard\StateDashboard;
use App\Traits\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */


class StateDashboardController extends Controller
{
    use Auth;

    private $Repository;
    private $_common;

    /**
     * | Ulb Wise Collection
     */
    public function ulbWiseCollection(Request $req)
    {
        try {
            $ulbs = UlbMaster::all();
            $year = Carbon::now()->year;

            if (isset($req->fyear))
                $year = substr($req->fyear, 0, 4);

            $financialYearStart = $year;
            if (Carbon::now()->month < 4) {
                $financialYearStart--;
            }

            $fromDate = $financialYearStart . '-04-01';
            $toDate   = $financialYearStart + 1 . '-03-31';
            $collection = collect();

            $ulbIds = $ulbs->pluck('id');

            foreach ($ulbIds as $ulbId) {
                $data['ulbId'] = $ulbId;
                $data['ulb'] = $ulbs->where('id', $ulbId)->firstOrFail()->ulb_name;
                $data['collection'] = $this->collection($ulbId, $fromDate, $toDate);
                $collection->push($data);
            }
            $collection = $collection->sortBy('ulbId')->values();

            ###################################################################################################
            # to be removed after akola project
            // $ward = UlbWardMaster::all();
            // $ward = DB::table('akola_wards')->get();
            // $wardIds = $ward->pluck('id');

            // foreach ($wardIds as $wardId) {
            //     $data['ulbId'] = $wardId;
            //     $data['ulb'] = $ward->where('id', $wardId)->firstOrFail()->ward_name;
            //     $data['collection'] = $ward->where('id', $wardId)->firstOrFail()->old_ward_name;
            //     $collection->push($data);
            // }
            // $collection = $collection->sortBy('ulbId')->values();

            return responseMsgs(true, "Ulb Wise Collection", remove_null($collection), "", '01', responseTime(), 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '01', responseTime(), 'Post', '');
        }
    }

    public function collection($ulbId, $fromDate, $toDate)
    {
        $sql = "WITH 
        transaction AS 
        (
            SELECT SUM(amount) AS total FROM prop_transactions
            WHERE ulb_id = $ulbId
            AND verify_status = 1
            AND tran_date BETWEEN '$fromDate' AND '$toDate'
        union
            (
            SELECT SUM(paid_amount) AS total FROM trade_transactions
            WHERE ulb_id = $ulbId
            AND is_verified = true
            AND tran_date BETWEEN '$fromDate' AND '$toDate'
            )
        union
            (
            SELECT SUM(amount) AS total FROM water_trans
            WHERE ulb_id = $ulbId
            AND verify_status = 1
            AND tran_date BETWEEN '$fromDate' AND '$toDate'
            )
        )select * from  transaction";
        $data = DB::select($sql);
        return collect($data)->pluck('total')->sum();
    }

    /**
     * | Module wise count of online payment
     */
    public function onlinePaymentCount(Request $req)
    {
        $starttime = microtime(true);
        $year = Carbon::now()->year;

        if (isset($req->fyear))
            $year = substr($req->fyear, 0, 4);

        $financialYearStart = $year;
        if (Carbon::now()->month < 4) {
            $financialYearStart--;
        }

        $fromDate =  $financialYearStart . '-04-01';
        $toDate   =  $financialYearStart + 1 . '-03-31';

        if ($req->financialYear) {
            $fy = explode('-', $req->financialYear);
            $strtYr = collect($fy)->first();
            $endYr = collect($fy)->last();
            $fromDate =  $strtYr . '-04-01';
            $toDate   =  $endYr . '-03-31';;
        }

        $propTran = PropTransaction::select('id')
            ->where('payment_mode', 'ONLINE')
            ->whereBetween('tran_date', [$fromDate, $toDate]);
        $tradeTran = TradeTransaction::select('id')
            ->where('payment_mode', 'Online')
            ->whereBetween('tran_date', [$fromDate, $toDate]);

        $waterTran = WaterTran::select('id')
            ->where('payment_mode', 'Online')
            ->whereBetween('tran_date', [$fromDate, $toDate]);

        $totalCount['propCount'] = $propTran->count();
        $totalCount['tradeCount'] = $tradeTran->count();
        $totalCount['waterCount'] = $waterTran->count();
        $totalCount['totalCount'] =  $propTran->union($tradeTran)->union($waterTran)->count();
        $endtime = microtime(true);
        $exeTime = ($endtime - $starttime) * 1000;
        return responseMsgs(true, "Online Payment Count", remove_null($totalCount), "", '', '01', "$exeTime ms", 'Post', '');
    }

    /**
     * | State Wise Collection Percentage
     */
    public function stateWiseCollectionPercentage(RequestCollectionPercentage $req)
    {
        try {
            $currentYear = Carbon::now()->format('Y');
            if (isset($req->month)) {
                $month = str_pad($req->month, 2, '0', STR_PAD_LEFT);
                $firstDate = date("Y-m-01", strtotime("$currentYear-$month-01"));
                $lastDate = date("Y-m-t", strtotime("$currentYear-$month-01"));
                $fromDate = $firstDate;
                $toDate   = $lastDate;
                $returnData = $this->getDataByCurrentMonth($req, $fromDate, $toDate);
                return responseMsgs(true, "state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
            }
            if (isset($req->month) && $req->year) {
                $fy = explode('-', $req->year);
                $strtYr = collect($fy)->first();
                $month = str_pad($req->month, 2, '0', STR_PAD_LEFT);
                $firstDate = date("Y-m-01", strtotime("$req->year-$month-01"));
                $lastDate = date("Y-m-t", strtotime("$req->year-$month-01"));
                $fromDate = $firstDate;
                $toDate   = $lastDate;
                $returnData = $this->getDataByMonthYear($req, $fromDate, $toDate);
                return responseMsgs(true, "state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
            }
            if (isset($req->year)) {
                $fy = explode('-', $req->year);
                $strtYr = collect($fy)->first();
                $endYr = collect($fy)->last();
                $fromDate = $strtYr . '-04-01';
                $toDate   =  $endYr . '-03-31';
                $returnData = $this->getDataByYear($fromDate, $toDate);
                return responseMsgs(true, "  state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
            } else {
                $financialYearStart = $currentYear;
                if (Carbon::now()->month < 4) {
                    $financialYearStart--;
                }
                $fromDate =  $financialYearStart  . '-04-01';
                $toDate   =  $financialYearStart + 1 . '-03-31';
                $returnData = $this->getDataByCurrentYear($fromDate, $toDate);
            }
            return responseMsgs(true, "state wise collection percentage!", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            dd($e->getLine());
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Details by Year
     */
    public function getDataByYear($fromDate, $toDate)
    {
        $prop = PropTransaction::select('amount as propAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select('amount as waterAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select('paid_amount as tradeAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $collectiveData['propAmount'] = (collect($prop->get())->sum('propAmount'));
        $collectiveData['waterAmount'] = (collect($water->get())->sum('waterAmount'));
        $collectiveData['tradeAmount'] = (collect($trade->get())->sum('tradeAmount'));

        $collectiveData['totalAount'] = round(collect($prop->get())->sum('propAmount') + collect($water->get())->sum('waterAmount') + collect($trade->get())->sum('tradeAmount'), 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }

    /**
     * | state wise collection data as per month
     * | @param 
     */
    public function getDataByCurrentYear($fromDate, $toDate)
    {
        $prop = PropTransaction::select('amount as propAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select('amount as waterAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select('paid_amount as tradeAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $collectiveData['propAmount'] = (collect($prop->get())->sum('propAmount'));
        $collectiveData['waterAmount'] = (collect($water->get())->sum('waterAmount'));
        $collectiveData['tradeAmount'] = (collect($trade->get())->sum('tradeAmount'));

        $collectiveData['totalAount'] = round(collect($prop->get())->sum('propAmount') + collect($water->get())->sum('waterAmount') + collect($trade->get())->sum('tradeAmount'), 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }

    /**
     * | get data by month and current year 
     */
    public function getDataByCurrentMonth($req, $fromDate, $toDate)
    {
        $prop = PropTransaction::select('amount as propAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select('amount as waterAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select('paid_amount as tradeAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $collectiveData['propAmount'] = (collect($prop->get())->sum('propAmount'));
        $collectiveData['waterAmount'] = (collect($water->get())->sum('waterAmount'));
        $collectiveData['tradeAmount'] = (collect($trade->get())->sum('tradeAmount'));

        $collectiveData['totalAount'] = round(collect($prop->get())->sum('propAmount') + collect($water->get())->sum('waterAmount') + collect($trade->get())->sum('tradeAmount'), 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }


    /**
     * | get data by month and year 
     */
    public function getDataByMonthYear($req, $fromDate, $toDate)
    {
        $prop = PropTransaction::select('amount as propAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $water = WaterTran::select('amount as waterAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);
        $trade = TradeTransaction::select('paid_amount as tradeAmount')
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->where("status", 1);

        $collectiveData['propAmount'] = (collect($prop->get())->sum('propAmount'));
        $collectiveData['waterAmount'] = (collect($water->get())->sum('waterAmount'));
        $collectiveData['tradeAmount'] = (collect($trade->get())->sum('tradeAmount'));

        $collectiveData['totalAount'] = round(collect($prop->get())->sum('propAmount') + collect($water->get())->sum('waterAmount') + collect($trade->get())->sum('tradeAmount'), 2);
        $collectiveData['propPercent'] = round(($collectiveData['propAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['waterPercent'] = round(($collectiveData['waterAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['tradePercent'] = round(($collectiveData['tradeAmount'] / $collectiveData['totalAount']) * 100, 2);
        $collectiveData['totalCount'] = $prop->union($water)->union($trade)->count();

        # Formating the data for returning
        $returData = [
            "totalCount" =>  $collectiveData['totalCount'],
            "totalAmount" => $collectiveData['totalAount'],
            "property" => [
                "propAmount" => round($collectiveData['propAmount'], 2),
                "propPercent" => $collectiveData['propPercent']
            ],
            "water" => [
                "waterAmount" => round($collectiveData['waterAmount'], 2),
                "waterPercent" => $collectiveData['waterPercent']
            ],
            "trade" => [
                "tradeAmount" => round($collectiveData['tradeAmount'], 2),
                "tradePercent" => $collectiveData['tradePercent']
            ]
        ];
        return $returData;
    }


    /**
     * | Ulb wise Data
     */
    public function districtWiseData(Request $req)
    {
        $req->validate([
            'districtCode' => 'required|integer'
        ]);

        try {
            $districtCode = $req->districtCode;
            $mUlbWardMstrs = new UlbMaster();
            $collection = collect();
            $data = collect();

            // Derivative Assignments
            $ulbs = $mUlbWardMstrs->getUlbsByDistrictCode($districtCode);
            if ($ulbs->isEmpty())
                throw new Exception("Ulbs Not Available for this district");

            $ulbIds = $ulbs->pluck('id');
            foreach ($ulbIds as $ulbId) {

                $sql = "SELECT gbsaf,mix_commercial,pure_commercial,pure_residential,total_properties
                FROM
                    ( select count(*) as gbsaf from prop_properties 
                        where is_gb_saf = 'true' and  ulb_id = $ulbId and status =1 ) as gbsaf,
                    ( select count(*) as mix_commercial from prop_properties
                        where holding_type = 'MIX_COMMERCIAL' and  ulb_id = $ulbId and status =1) as mix_commercial,
                    (select count(*) as pure_commercial from prop_properties
                        where holding_type = 'PURE_COMMERCIAL'and  ulb_id = $ulbId and status =1) as pure_commercial,
                    (select count(*) as pure_residential from prop_properties
                        where holding_type = 'PURE_RESIDENTIAL'and  ulb_id = $ulbId and status =1) as pure_residential,
                    (select count(*) as total_properties from prop_properties where ulb_id = $ulbId and status =1) as total_properties";

                $a = DB::select($sql);

                $data = collect($a)->first();
                $data = json_decode(json_encode($data), true);
                $data['ulb'] = $ulbs->where('id', $ulbId)->firstOrFail()->ulb_name;

                $collection->push($data);
            }
            $data = (array_values(objtoarray($collection)));
            return responseMsgs(true, "District Wise Collection", remove_null($data));
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), remove_null($data));
        }
    }

    # dashboard repots
    public function stateDashboardDCB(Request $request, StateDashboard $Repository)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["ds11.1", 1.1, null, $request->getMethod(), null,]]);
        return $Repository->stateDashboardDCB($request);
    }
}

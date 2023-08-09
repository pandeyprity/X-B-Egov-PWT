<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Payment\RevDailycollection;
use App\Models\Payment\RevDailycollectiondetail;
use App\Models\Payment\TempTransaction;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-31-01-2023 
 * | Created by-Mrinal Kumar
 * | Payment Cash Verification
 */

class CashVerificationController extends Controller
{
    /**
     * | Unverified Cash Verification List
     * | Serial : 1
     */
    public function cashVerificationList(Request $request)
    {
        try {
            $ulbId =  authUser($request)->ulb_id;
            $userId =  $request->id;
            $date = date('Y-m-d', strtotime($request->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mTempTransaction =  new TempTransaction();

            if (isset($userId)) {
                $data = $mTempTransaction->transactionDtl($date, $ulbId)
                    ->where('user_id', $userId)
                    ->get();
            }

            if (!isset($userId)) {
                $data = $mTempTransaction->transactionDtl($date, $ulbId)
                    ->get();
            }

            $collection = collect($data->groupBy("id")->all());

            $data = $collection->map(function ($val) use ($date, $propertyModuleId, $waterModuleId, $tradeModuleId) {
                $total =  $val->sum('amount');
                $prop  = $val->where("module_id", $propertyModuleId)->sum('amount');
                $water = $val->where("module_id", $waterModuleId)->sum('amount');
                $trade = $val->where("module_id", $tradeModuleId)->sum('amount');
                return [
                    "id" => $val[0]['id'],
                    "user_name" => $val[0]['name'],
                    "property" => $prop,
                    "water" => $water,
                    "trade" => $trade,
                    "total" => $total,
                    "date" => Carbon::parse($date)->format('d-m-Y'),
                    // "verified_amount" => 0,
                ];
            });

            $data = (array_values(objtoarray($data)));

            // $property = collect($data)->where('module_id', $propertyModuleId);
            // $water = collect($data)->where('module_id', $waterModuleId);
            // $trade = collect($data)->where('module_id', $tradeModuleId);

            // $total['property'] =  collect($property)->map(function ($value) {
            //     return $value['amount'];
            // })->sum();

            // $total['water'] =  collect($water)->map(function ($value) {
            //     return $value['amount'];
            // })->sum();

            // $total['trade'] =  collect($trade)->map(function ($value) {
            //     return $value['amount'];
            // })->sum();

            // $total['total'] = collect($total)->sum();
            // $total['date'] = $date;

            return responseMsgs(true, "List cash Verification", $data, "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Verified Cash Verification List
     * | Serial : 2
     */
    public function verifiedCashVerificationList(Request $req)
    {
        try {
            $ulbId =  authUser($request)->ulb_id;
            $userId =  $req->id;
            $date = date('Y-m-d', strtotime($req->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');

            $revDailycollection =  RevDailycollection::select('users.id', 'name', 'deposit_amount', 'module_id', 'tran_no')
                ->join('rev_dailycollectiondetails as rdc', 'rdc.collection_id', 'rev_dailycollections.id')
                ->join('users', 'users.id', 'rev_dailycollections.tc_id')
                ->groupBy('users.id', 'name', 'rdc.deposit_amount', 'module_id', 'tran_no')
                ->where('deposit_date', $date)
                ->get();
            $collection = collect($revDailycollection->groupBy("id")->all());

            $data = $collection->map(function ($val) use ($date, $propertyModuleId, $waterModuleId, $tradeModuleId) {
                $total =  $val->sum('deposit_amount');
                $prop = $val->where("module_id", $propertyModuleId)->sum('deposit_amount');
                $water = $val->where("module_id", $waterModuleId)->sum('deposit_amount');
                $trade = $val->where("module_id", $tradeModuleId)->sum('deposit_amount');
                return [
                    "id" => $val[0]['id'],
                    "user_name" => $val[0]['name'],
                    "property" => $prop,
                    "water" => $water,
                    "trade" => $trade,
                    "total" => $total,
                    "date" => Carbon::parse($date)->format('d-m-Y'),
                    "verifyStatus" => true,
                    "tranNo" => $val[0]['tran_no'],
                ];
            });

            $data = (array_values(objtoarray($data)));

            return responseMsgs(true, "TC Collection", remove_null($data), "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }



    /**
     * | Verified Cash Verification List
     */

    // public function verifiedCashVerificationList(Request $req)
    // {
    //     $ulbId =  authUser()->ulb_id;
    //     $userId =  $req->id;
    //     $date = date('Y-m-d', strtotime($req->date));

    //     DB::enableQueryLog();
    //     $propTraDtl = PropTransaction::select(
    //         'users.id',
    //         'users.user_name',
    //         DB::raw("sum(prop_transactions.amount) as amount,'property' as module,
    //         sum(case when prop_transactions.verify_status = 1 then prop_transactions.amount  end ) as verified_amount,
    //         string_agg((case when prop_transactions.verify_status = 1 then 1  end)::text,',') As verify_status
    //         "),
    //     )
    //         ->join('users', 'users.id', 'prop_transactions.user_id')
    //         ->where('tran_date', $date)
    //         ->where('prop_transactions.status', '<>', 0)
    //         ->where('payment_mode', '!=', 'ONLINE')
    //         ->groupBy(["users.id", "users.user_name"]);

    //     $tradeDtl  = TradeTransaction::select(
    //         'users.id',
    //         'users.user_name',
    //         DB::raw("sum(trade_transactions.paid_amount) as amount,'trade' as module , 
    //         sum(case when trade_transactions.is_verified is true  then trade_transactions.paid_amount end ) as verified_amount,
    //         string_agg((case when trade_transactions.is_verified is true then 1  end)::text,',') As verify_status
    //         "),
    //     )
    //         ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
    //         ->where('tran_date', $date)
    //         ->where('trade_transactions.status', '<>', 0)
    //         ->where('payment_mode', '!=', 'ONLINE')
    //         ->groupBy(["users.id", "users.user_name"]);

    //     $waterDtl = WaterTran::select(
    //         'users.id',
    //         'users.user_name',
    //         DB::raw("sum(water_trans.amount) as amount,'water' as module,
    //         sum(case when water_trans.verify_status =1  then water_trans.amount  end ) as verified_amount,
    //         string_agg((case when water_trans.verify_status =1 then 1 end)::text,',') As verify_status
    //         "),
    //     )
    //         ->join('users', 'users.id', 'water_trans.emp_dtl_id')
    //         ->where('tran_date', $date)
    //         ->where('water_trans.status', '<>', 0)
    //         ->where('payment_mode', '!=', 'ONLINE')
    //         ->groupBy(["users.id", "users.user_name"]);
    //     if ($userId) {
    //         $propTraDtl = $propTraDtl->where('user_id', $userId);
    //         $tradeDtl = $tradeDtl->where('emp_dtl_id', $userId);
    //         $waterDtl = $waterDtl->where('emp_dtl_id', $userId);
    //     }
    //     $propTraDtl1 = $propTraDtl;
    //     $collection = $propTraDtl1
    //         ->union($tradeDtl)
    //         ->union($waterDtl)
    //         ->get();
    //     $collection = collect($collection->groupBy("id")->all());
    //     // dd($collection);
    //     $data = $collection->map(function ($val) use ($date) {
    //         $total =  $val->sum('amount');
    //         $verified_amount =  $val->sum('verified_amount');
    //         $prop = $val->where("module", "property")->sum('amount');
    //         $trad = $val->where("module", "trade")->sum('amount');
    //         $water = $val->where("module", "water")->sum('amount');
    //         $is_verified = in_array(0, (objToArray(collect(explode(',', ($val->implode("verify_status", ',')))))));
    //         return [

    //             "id" => $val[0]['id'],
    //             "user_name" => $val[0]['user_name'],
    //             "property" => $prop,
    //             "water" => $water,
    //             "trade" => $trad,
    //             "total" => $total,
    //             // "is_verified" => $is_verified,
    //             "date" => $date,
    //             "verified_amount" => $verified_amount,
    //         ];
    //     });
    //     $data = (array_values(objtoarray($data)));

    //     return responseMsgs(true, "Verified List cash Verification", $data, "010201", "1.0", "", "POST", $req->deviceId ?? "");
    // }


    /**
     * | Tc Collection Dtl
     * | Serial : 3
     */
    public function tcCollectionDtl(Request $request)
    {
        try {
            $request->validate([
                "date" => "required|date",
                "userId" => "required|numeric",

            ]);
            $userId =  $request->userId;
            $ulbId =  authUser($request)->ulb_id;
            $date = date('Y-m-d', strtotime($request->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mTempTransaction = new TempTransaction();
            $details = $mTempTransaction->transactionList($date, $userId, $ulbId);
            if ($details->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['property'] = collect($details)->where('module_id', $propertyModuleId)->values();
            $data['water'] = collect($details)->where('module_id', $waterModuleId)->values();
            $data['trade'] = collect($details)->where('module_id', $tradeModuleId)->values();
            $data['Cash'] = collect($details)->where('payment_mode', '=', 'CASH')->sum('amount');
            $data['Cheque'] = collect($details)->where('payment_mode', '=', 'CHEQUE')->sum('amount');
            $data['DD'] = collect($details)->where('payment_mode', '=', 'DD')->sum('amount');
            // $data['Neft'] = collect($details)->where('payment_mode', '=', 'Neft')->first()->amount;
            // $data['RTGS'] = collect($details)->where('payment_mode', '=', 'RTGS')->first()->amount;
            $data['totalAmount'] =  $details->sum('amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['collectorName'] =  collect($details)[0]->user_name;
            $data['date'] = Carbon::parse($date)->format('d-m-Y');
            $data['verifyStatus'] = false;

            return responseMsgs(true, "TC Collection", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Verified tc collection
     * | Serial : 4
     */
    public function verifiedTcCollectionDtl(Request $request)
    {
        try {
            $request->validate([
                "date" => "required|date",
                "userId" => "required|numeric",
            ]);
            $userId =  $request->userId;
            $ulbId =  authUser($request)->ulb_id;
            $date = date('Y-m-d', strtotime($request->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');

            $mRevDailycollection = new RevDailycollection();
            $details = $mRevDailycollection->collectionDetails($ulbId)
                ->where('deposit_date', $date)
                ->where('tc_id', $userId)
                ->get();

            if ($details->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['property'] = collect($details)->where('module_id', $propertyModuleId)->values();
            $data['water'] = collect($details)->where('module_id', $waterModuleId)->values();
            $data['trade'] = collect($details)->where('module_id', $tradeModuleId)->values();

            $data['Cash'] = collect($details)->where('payment_mode', 'CASH')->sum('amount');
            $data['Cheque'] = collect($details)->where('payment_mode', 'CHEQUE')->sum('amount');
            $data['DD'] = collect($details)->where('payment_mode', 'DD')->sum('amount');

            $data['totalAmount'] =  $details->sum('amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['collectorName']   =  collect($details)[0]->tc_name;
            $data['collectorMobile'] =  collect($details)[0]->tc_mobile;
            $data['verifierName']    =  collect($details)[0]->verifier_name;
            $data['verifierMobile']  =  collect($details)[0]->verifier_mobile;
            $data['tranNo']  =  collect($details)[0]->tran_no;
            $data['verifyStatus']    =  true;
            $data['date'] = Carbon::parse($date)->format('d-m-Y');

            return responseMsgs(true, "TC Collection", remove_null($data), "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Verified tc collection
     * | Serial : 4
     */
    // public function verifiedTcCollectionDtl(Request $request)
    // {
    //     $request->validate([
    //         "date" => "required|date",
    //         "userId" => "required|numeric",

    //     ]);

    //     $userId = $request->userId;
    //     $date = date('Y-m-d', strtotime($request->date));

    //     $sql =   "WITH 
    //         prop_transactions AS 
    //     (
    //         SELECT prop_transactions.id, saf_no AS application_no,tran_no,
    //         payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
    //                 prop_active_safs.ward_mstr_id AS ward_id , owner_name,'activ_saf' AS tbl
    //             FROM prop_transactions
    //             inner join prop_active_safs on prop_active_safs.id = prop_transactions.saf_id
    //             inner join ulb_ward_masters on ulb_ward_masters.id = prop_active_safs.ward_mstr_id
    //             LEFT JOIN (
    //                 SELECT prop_active_safs_owners.saf_id,string_agg(owner_name,',') AS owner_name
    //                 FROM prop_active_safs_owners
    //                 JOIN prop_transactions ON prop_transactions.saf_id = prop_active_safs_owners.saf_id
    //                 WHERE prop_active_safs_owners.status = 1
    //                     AND prop_transactions.status = 1
    //                     AND prop_transactions.tran_date = '" . $date . "'
    //                     AND payment_mode != 'netbanking'
    //                     AND prop_transactions.payment_mode != 'ONLINE'
    //                 GROUP BY prop_active_safs_owners.saf_id
    //             ) owners ON owners.saf_id = prop_active_safs.id
    //             WHERE prop_transactions.status = 1 
    //                 AND prop_transactions.tran_date = '" . $date . "'
    //                 AND prop_transactions.payment_mode != 'ONLINE'
    //                 AND payment_mode != 'netbanking'
    //                 AND prop_transactions.user_id = $userId
    //         union
    //             (
    //                 SELECT prop_transactions.id, saf_no AS application_no,tran_no,
    //                 payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
    //                     prop_rejected_safs.ward_mstr_id AS ward_id , owner_name,'rejected_saf' AS tbl
    //                 FROM prop_transactions
    //                 inner join prop_rejected_safs on prop_rejected_safs.id = prop_transactions.saf_id
    //                 inner join ulb_ward_masters on ulb_ward_masters.id = prop_rejected_safs.ward_mstr_id
    //                 LEFT JOIN (
    //                     SELECT prop_rejected_safs_owners.saf_id,string_agg(owner_name,',') AS owner_name
    //                     FROM prop_rejected_safs_owners
    //                     JOIN prop_transactions ON prop_transactions.saf_id = prop_rejected_safs_owners.saf_id
    //                     WHERE prop_rejected_safs_owners.status = 1
    //                         AND prop_transactions.status = 1
    //                         AND prop_transactions.tran_date = '2023-02-01'
    //                         AND payment_mode != 'netbanking'
    //                         AND prop_transactions.payment_mode != 'ONLINE'
    //                     GROUP BY prop_rejected_safs_owners.saf_id
    //                 ) owners ON owners.saf_id = prop_rejected_safs.id
    //                 WHERE prop_transactions.status = 1 
    //                     AND prop_transactions.tran_date = '" . $date . "'
    //                     AND prop_transactions.payment_mode != 'ONLINE'
    //                     AND payment_mode != 'netbanking'
    //                     AND prop_transactions.user_id = $userId
    //             )
    //         union
    //             (
    //                 SELECT prop_transactions.id, saf_no AS application_no,
    //                 tran_no,payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
    //                     prop_safs.ward_mstr_id AS ward_id , owner_name,'prop_saf' AS tbl
    //                 FROM prop_transactions
    //                 inner join prop_safs on prop_safs.id = prop_transactions.saf_id
    //                 inner join ulb_ward_masters on ulb_ward_masters.id = prop_safs.ward_mstr_id
    //                 LEFT JOIN (
    //                     SELECT prop_safs_owners.saf_id,string_agg(owner_name,',') AS owner_name
    //                     FROM prop_safs_owners
    //                     JOIN prop_transactions ON prop_transactions.saf_id = prop_safs_owners.saf_id
    //                     WHERE prop_safs_owners.status = 1
    //                         AND prop_transactions.status = 1
    //                         AND prop_transactions.tran_date = '" . $date . "'
    //                         AND payment_mode != 'netbanking'
    //                         AND prop_transactions.payment_mode != 'ONLINE'
    //                     GROUP BY prop_safs_owners.saf_id
    //                 ) owners ON owners.saf_id = prop_safs.id
    //                 WHERE prop_transactions.status = 1 
    //                     AND prop_transactions.tran_date = '" . $date . "'
    //                     AND prop_transactions.payment_mode != 'ONLINE'
    //                     AND payment_mode != 'netbanking'
    //                     AND prop_transactions.user_id = $userId
    //             )
    //         union
    //             (
    //                 SELECT prop_transactions.id, holding_no AS application_no,tran_no,
    //                 payment_mode,amount,verify_status,verified_by,verify_date,ward_name,tran_date,
    //                     prop_properties.ward_mstr_id AS ward_id , owner_name,'prop_properties' AS tbl
    //                 FROM prop_transactions
    //                 inner join prop_properties on prop_properties.id = prop_transactions.property_id
    //                 inner join ulb_ward_masters on ulb_ward_masters.id = prop_properties.ward_mstr_id
    //                 LEFT JOIN (
    //                     SELECT prop_owners.property_id,string_agg(owner_name,',') AS owner_name
    //                     FROM prop_owners
    //                     JOIN prop_transactions ON prop_transactions.property_id = prop_owners.property_id
    //                     WHERE prop_owners.status = 1
    //                         AND prop_transactions.status = 1
    //                         AND prop_transactions.tran_date = '" . $date . "'
    //                         AND prop_transactions.payment_mode != 'ONLINE'
    //                         AND payment_mode != 'netbanking'
    //                     GROUP BY prop_owners.property_id
    //                 ) owners ON owners.property_id = prop_properties.id
    //                 WHERE prop_transactions.status = 1 
    //                     AND prop_transactions.tran_date = '" . $date . "'
    //                     AND prop_transactions.payment_mode != 'ONLINE'
    //                     AND payment_mode != 'netbanking'
    //                     AND prop_transactions.user_id = $userId
    //             )
    //     )select * from  prop_transactions;";

    //     // trade
    //     $trade =   "WITH 
    //         trade_transaction AS 
    //     (
    //         SELECT trade_transactions.id,tran_no,
    //             payment_mode,paid_amount as amount,is_verified as verify_status,verify_by as verified_by,verify_date,ward_name,application_no,
    //             tran_type,tran_date,owner_name,'active_trade_licences' AS tbl
    //         FROM trade_transactions
    //         inner join active_trade_licences on active_trade_licences.id = trade_transactions.temp_id
    //         inner join ulb_ward_masters on ulb_ward_masters.id = trade_transactions.ward_id
    //         LEFT JOIN (
    //             SELECT active_trade_owners.temp_id,string_agg(owner_name,',') AS owner_name
    //             FROM active_trade_owners
    //             JOIN trade_transactions ON trade_transactions.temp_id = active_trade_owners.temp_id
    //             WHERE active_trade_owners.is_active = true
    //                 AND trade_transactions.status = 1
    //                 AND trade_transactions.is_verified = true
    //                 AND trade_transactions.tran_date = '" . $date . "'
    //                 AND payment_mode != 'netbanking'
    //                 AND trade_transactions.payment_mode != 'ONLINE'
    //             GROUP BY active_trade_owners.temp_id
    //         ) owners ON owners.temp_id = active_trade_licences.id
    //         WHERE trade_transactions.status = 1 
    //             AND trade_transactions.tran_date = '" . $date . "'
    //             AND trade_transactions.payment_mode != 'ONLINE'
    //             AND payment_mode != 'netbanking'
    //             AND trade_transactions.is_verified = true
    //             AND emp_dtl_id = $userId
    //     union
    //         (
    //         SELECT trade_transactions.id,tran_no,
    //             payment_mode,paid_amount as amount,is_verified as verify_status,verify_by as verified_by,verify_date,ward_name,application_no,
    //             tran_type,tran_date,owner_name,'trade_licences' AS tbl
    //         FROM trade_transactions
    //         inner join trade_licences on trade_licences.id = trade_transactions.temp_id
    //         inner join ulb_ward_masters on ulb_ward_masters.id = trade_transactions.ward_id
    //         LEFT JOIN (
    //             SELECT trade_owners.temp_id,string_agg(owner_name,',') AS owner_name
    //             FROM trade_owners
    //             JOIN trade_transactions ON trade_transactions.temp_id = trade_owners.temp_id
    //             WHERE trade_owners.is_active = true
    //                 AND trade_transactions.status = 1
    //                 AND trade_transactions.is_verified = true
    //                 AND trade_transactions.tran_date = '" . $date . "'
    //                 AND payment_mode != 'netbanking'
    //                 AND trade_transactions.payment_mode != 'ONLINE'
    //             GROUP BY trade_owners.temp_id
    //         ) owners ON owners.temp_id = trade_licences.id
    //         WHERE trade_transactions.status = 1 
    //             AND trade_transactions.tran_date = '" . $date . "'
    //             AND trade_transactions.payment_mode != 'ONLINE'
    //             AND payment_mode != 'netbanking'
    //             AND trade_transactions.is_verified = true
    //             AND emp_dtl_id = $userId
    //         )
    //     union
    //         (
    //         SELECT trade_transactions.id,tran_no,
    //             payment_mode,paid_amount as amount,is_verified as verify_status,verify_by as verified_by,verify_date,ward_name,application_no,
    //             tran_type,tran_date,owner_name,'rejected_trade_licences' AS tbl
    //         FROM trade_transactions
    //         inner join rejected_trade_licences on rejected_trade_licences.id = trade_transactions.temp_id
    //         inner join ulb_ward_masters on ulb_ward_masters.id = trade_transactions.ward_id
    //         LEFT JOIN (
    //             SELECT rejected_trade_owners.temp_id,string_agg(owner_name,',') AS owner_name
    //             FROM rejected_trade_owners
    //             JOIN trade_transactions ON trade_transactions.temp_id = rejected_trade_owners.temp_id
    //             WHERE rejected_trade_owners.is_active = true
    //                 AND trade_transactions.status = 1
    //                 AND trade_transactions.is_verified = true
    //                 AND trade_transactions.tran_date = '" . $date . "'
    //                 AND payment_mode != 'netbanking'
    //                 AND trade_transactions.payment_mode != 'ONLINE'
    //             GROUP BY rejected_trade_owners.temp_id
    //         ) owners ON owners.temp_id = rejected_trade_licences.id
    //         WHERE trade_transactions.status = 1 
    //             AND trade_transactions.tran_date = '" . $date . "'
    //             AND trade_transactions.payment_mode != 'ONLINE'
    //             AND payment_mode != 'netbanking'
    //             AND trade_transactions.is_verified = true
    //             AND emp_dtl_id = $userId
    //         )
    //     )select * from  trade_transaction;";

    //     //water
    //     $water =   "WITH 
    //         water_transaction AS 
    //     (
    //         SELECT water_trans.id,tran_no,
    //         payment_mode,amount,verify_status,verified_by,verified_date as verify_date,ward_name,tran_date,application_no,tran_type,
    //                 owner_name,'water_active' AS tbl
    //             FROM water_trans
    //             inner join water_applications on water_applications.id = water_trans.related_id
    //             inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
    //             LEFT JOIN (
    //                 SELECT water_applicants.application_id,string_agg(applicant_name,',') AS owner_name
    //                 FROM water_applicants
    //                 JOIN water_trans ON water_trans.related_id = water_applicants.application_id
    //                 WHERE water_applicants.status = true
    //                     AND water_trans.status = 1
    //                     AND water_trans.verify_status = 1
    //                     AND water_trans.tran_date = '" . $date . "'
    //                     AND payment_mode != 'netbanking'
    //                     AND water_trans.payment_mode != 'Online'
    //                 GROUP BY water_applicants.application_id
    //             ) owners ON owners.application_id = water_applications.id
    //             WHERE water_trans.status = 1 
    //         		AND water_trans.tran_date = '" . $date . "'
    //                 AND water_trans.payment_mode != 'Online'
    //                 AND payment_mode != 'netbanking'
    //                 AND water_trans.verify_status = 1
    //                 AND emp_dtl_id = $userId

    //     union
    //         (
    //         SELECT water_trans.id,tran_no,
    //         payment_mode,amount,verify_status,verified_by,verified_date as verify_date,ward_name,tran_date,application_no,tran_type,
    //                 owner_name,'water_approved' AS tbl
    //             FROM water_trans
    //             inner join water_approval_application_details on water_approval_application_details.id = water_trans.related_id
    //             inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
    //             LEFT JOIN (
    //                 SELECT water_approval_applicants.application_id,string_agg(applicant_name,',') AS owner_name
    //                 FROM water_approval_applicants
    //                 JOIN water_trans ON water_trans.related_id = water_approval_applicants.application_id
    //                 WHERE water_approval_applicants.status = true
    //                     AND water_trans.status = 1
    //                     AND water_trans.verify_status = 1
    //                     AND water_trans.tran_date = '" . $date . "'
    //                     AND payment_mode != 'netbanking'
    //                     AND water_trans.payment_mode != 'Online'
    //                 GROUP BY water_approval_applicants.application_id
    //             ) owners ON owners.application_id = water_approval_application_details.id
    //             WHERE water_trans.status = 1 
    //         		AND water_trans.tran_date = '" . $date . "'
    //                 AND water_trans.payment_mode != 'Online'
    //                 AND payment_mode != 'netbanking'
    //                 AND water_trans.verify_status = 1
    //                 AND emp_dtl_id = $userId
    //         )
    //     union
    //         (
    //         SELECT water_trans.id,tran_no,
    //         payment_mode,amount,verify_status,verified_by,verified_date as verify_date,ward_name,tran_date,application_no,tran_type,
    //                 owner_name,'water_rejected' AS tbl
    //             FROM water_trans
    //             inner join water_rejection_application_details on water_rejection_application_details.id = water_trans.related_id
    //             inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
    //             LEFT JOIN (
    //                 SELECT water_rejection_applicants.application_id,string_agg(applicant_name,',') AS owner_name
    //                 FROM water_rejection_applicants
    //                 JOIN water_trans ON water_trans.related_id = water_rejection_applicants.application_id
    //                 WHERE water_rejection_applicants.status = true
    //                     AND water_trans.status = 1
    //                     AND water_trans.verify_status = 1
    //                     AND water_trans.tran_date = '" . $date . "'
    //                     AND payment_mode != 'netbanking'
    //                     AND water_trans.payment_mode != 'Online'
    //                 GROUP BY water_rejection_applicants.application_id
    //             ) owners ON owners.application_id = water_rejection_application_details.id
    //             WHERE water_trans.status = 1 
    //         		AND water_trans.tran_date = '" . $date . "'
    //                 AND water_trans.payment_mode != 'Online'
    //                 AND payment_mode != 'netbanking'
    //                 AND water_trans.verify_status = 1
    //                 AND emp_dtl_id = $userId
    //         )

    //     union
    //         (

    //         SELECT water_trans.id,tran_no,
    //         payment_mode,amount,verify_status,verified_by,verified_date as verify_date,ward_name,tran_date,consumer_no,tran_type,
    //                 owner_name,'water_consumer' AS tbl
    //             FROM water_trans
    //             inner join water_consumers on water_consumers.id = water_trans.related_id
    //             inner join ulb_ward_masters on ulb_ward_masters.id = water_trans.ward_id
    //             LEFT JOIN (
    //                 SELECT water_consumer_owners.consumer_id,string_agg(applicant_name,',') AS owner_name
    //                 FROM water_consumer_owners
    //                 JOIN water_trans ON water_trans.related_id = water_consumer_owners.consumer_id
    //                 WHERE water_consumer_owners.status = true
    //                     AND water_trans.status = 1
    //                     AND water_trans.verify_status = 1
    //                     AND water_trans.tran_date = '" . $date . "'
    //                     AND payment_mode != 'netbanking'
    //                     AND water_trans.payment_mode != 'Online'
    //                 GROUP BY water_consumer_owners.consumer_id
    //             ) owners ON owners.consumer_id = water_consumers.id
    //             WHERE water_trans.status = 1 
    //         		AND water_trans.tran_date = '" . $date . "'
    //                 AND water_trans.payment_mode != 'Online'
    //                 AND payment_mode != 'netbanking'
    //                 AND water_trans.verify_status = 1
    //                 AND emp_dtl_id = $userId
    //         )
    //     )select * from  water_transaction;";

    //     $data['property'] =  DB::select($sql);
    //     $data['trade'] =  DB::select($trade);
    //     return $data['water'] =  DB::select($water);

    //     $total['property'] =  collect($data['property'])->map(function ($value) {
    //         return $value->amount;
    //     })->sum();

    //     $total['trade'] =  collect($data['trade'])->map(function ($value) {
    //         return $value->amount;
    //     })->sum();

    //     $total['water'] =  collect($data['water'])->map(function ($value) {
    //         return $value->amount;
    //     })->sum();

    //     $data['totalAmount'] = collect($total)->sum();
    //     $data['date'] = $date;

    //     return responseMsgs(true, "TC Collection", $data, "010201", "1.0", "", "POST", $request->deviceId ?? "");
    // }

    /**
     * | For Verification of cash
     * | serial : 5
     */
    public function cashVerify(Request $request)
    {
        try {
            $user = authUser($request);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $property =  $request->property;
            $water    =  $request->water;
            $trade    =  $request->trade;
            $mRevDailycollection = new RevDailycollection();
            $cashParamId = Config::get('PropertyConstaint.CASH_VERIFICATION_PARAM_ID');

            DB::beginTransaction();
            $idGeneration = new PrefixIdGenerator($cashParamId, $ulbId);
            $tranNo = $idGeneration->generate();

            $mReqs = new Request([
                "tran_no" => $tranNo,
                "user_id" => $userId,
                "demand_date" => Carbon::now(),  //   <- to be changed
                "deposit_date" => Carbon::now(),
                "ulb_id" => $ulbId,
                "tc_id" => 1,                    //   <- to be changed
            ]);
            $collectionId =  $mRevDailycollection->store($mReqs);

            if ($property) {
                foreach ($property as $propertyDtl) {
                    $pTempTransaction = TempTransaction::find($propertyDtl['id']);
                    $tran_no =  $propertyDtl['tran_no'];
                    PropTransaction::where('tran_no', $tran_no)
                        ->update(
                            [
                                'verify_status' => 1,
                                'verify_date' => Carbon::now(),
                                'verified_by' => $userId
                            ]
                        );
                    $this->dailyCollectionDtl($propertyDtl, $collectionId);
                    if (!$pTempTransaction)
                        throw new Exception("No Transaction Found for this id");

                    $logTrans = $pTempTransaction->replicate();
                    $logTrans->setTable('log_temp_transactions');
                    $logTrans->id = $pTempTransaction->id;
                    $logTrans->save();
                    $pTempTransaction->delete();
                }
            }

            if ($water) {
                foreach ($water as $waterDtl) {
                    $wTempTransaction = TempTransaction::find($waterDtl['id']);
                    WaterTran::where('tran_no', $waterDtl['tran_no'])
                        ->update(
                            [
                                'verify_status' => 1,
                                'verified_date' => Carbon::now(),
                                'verified_by' => $userId
                            ]
                        );
                    $this->dailyCollectionDtl($waterDtl, $collectionId);
                    if (!$wTempTransaction)
                        throw new Exception("No Transaction Found for this id");

                    $logTrans = $wTempTransaction->replicate();
                    $logTrans->setTable('log_temp_transactions');
                    $logTrans->id = $wTempTransaction->id;
                    $logTrans->save();
                    $wTempTransaction->delete();
                }
            }

            if ($trade) {
                foreach ($trade as $tradeDtl) {
                    $tTempTransaction = TempTransaction::find($tradeDtl['id']);
                    TradeTransaction::where('tran_no', $tradeDtl['tran_no'])
                        ->update(
                            [
                                'is_verified' => 1,
                                'verify_date' => Carbon::now(),
                                'verify_by' => $userId
                            ]
                        );
                    $this->dailyCollectionDtl($tradeDtl, $collectionId);
                    if (!$tTempTransaction)
                        throw new Exception("No Transaction Found for this id");

                    $logTrans = $tTempTransaction->replicate();
                    $logTrans->setTable('log_temp_transactions');
                    $logTrans->id = $tTempTransaction->id;
                    $logTrans->save();
                    $tTempTransaction->delete();
                }
            }
            DB::commit();
            return responseMsgs(true, "Cash Verified", '', "010201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    /**
     * | serial : 5.1
     */
    public function dailyCollectionDtl($tranDtl, $collectionId)
    {
        $RevDailycollectiondetail = new RevDailycollectiondetail();
        $mReqs = new Request([
            "collection_id" => $collectionId,
            "module_id" => $tranDtl['module_id'],
            "demand" => $tranDtl['amount'],
            "deposit_amount" => $tranDtl['amount'],
            "cheq_dd_no" => $tranDtl['cheque_dd_no'],
            "bank_name" => $tranDtl['bank_name'],
            "deposit_mode" => strtoupper($tranDtl['payment_mode']),
            "application_no" => $tranDtl['application_no'],
            "transaction_id" => $tranDtl['id']
        ]);
        $RevDailycollectiondetail->store($mReqs);
    }

    /**
     * | Cash Verification Receipt
     */
    public function cashReceipt(Request $request)
    {
        $request->validate([
            'receiptNo' => 'required'
        ]);
        try {
            $ulbId = authUser($request)->ulb_id;
            $mUlbMasters = new UlbMaster();
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');

            $mRevDailycollection = new RevDailycollection();
            $details = $mRevDailycollection->collectionDetails($ulbId)
                ->where('rev_dailycollections.tran_no', $request->receiptNo)
                ->get();

            if ($details->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['property'] = collect($details)->where('module_id', $propertyModuleId)->values();
            $data['water']    = collect($details)->where('module_id', $waterModuleId)->values();
            $data['trade']    = collect($details)->where('module_id', $tradeModuleId)->values();

            $data['Cash']   = collect($details)->where('payment_mode', 'CASH')->sum('amount');
            $data['Cheque'] = collect($details)->where('payment_mode', 'CHEQUE')->sum('amount');
            $data['DD']     = collect($details)->where('payment_mode', 'DD')->sum('amount');

            $data['totalAmount'] =  $details->sum('amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['collectorName']       =  collect($details)[0]->tc_name;
            $data['collectorMobile']     =  collect($details)[0]->tc_mobile;
            $data['verifierName']        =  collect($details)[0]->verifier_name;
            $data['verifierMobile']      =  collect($details)[0]->verifier_mobile;
            $data['receiptNo']           =  collect($details)[0]->tran_no;
            $data['verificationDate']    =  collect($details)[0]->verification_date;
            $data['ulb']                 =  collect($details)[0]->ulb_name;
            $data['ulbDetails']          = $mUlbMasters->getUlbDetails($ulbId);

            return responseMsgs(true, "Cash Receipt", $data, "010201", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Edit Cheque No
       | Currently not in use
     */
    public function editChequeNo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'moduleId' => 'required|numeric',
            'chequeNo' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ], 401);
        }
        try {

            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $tranDtl = TempTransaction::find($request->id);
            $tranId = $tranDtl->transaction_id;

            DB::beginTransaction();
            $tranDtl
                ->update(
                    ['cheque_dd_no' => $request->chequeNo]
                );

            if ($request->moduleId == $propertyModuleId) {
                PropChequeDtl::where('transaction_id', $tranId)
                    ->update(
                        ['cheque_no' => $request->chequeNo]
                    );
            }

            if ($request->moduleId == $waterModuleId) {
                WaterChequeDtl::where('transaction_id', $tranId)
                    ->update(
                        ['cheque_no' => $request->chequeNo]
                    );
            }

            if ($request->moduleId == $tradeModuleId) {
                TradeChequeDtl::where('tran_id', $tranId)
                    ->update(
                        ['cheque_no' => $request->chequeNo]
                    );
            }

            DB::commit();
            return responseMsgs(true, "Edit Successful", "", "010201", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        }
    }
}

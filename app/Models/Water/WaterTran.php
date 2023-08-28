<?php

namespace App\Models\Water;

use App\MicroServices\IdGeneration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WaterTran extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection = 'pgsql_water';

    /**
     * |--------------- Get Transaction Data -----------|
     */
    public function getTransactionDetailsById($req)
    {
        return WaterTran::where('related_id', $req)
            ->get();
    }

    /**
     * |---------------- Get transaction by the transaction details ---------------|
     */
    public function getTransNo($applicationId, $applicationFor)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "<>", "Demand Collection")
            ->where('status', 1);
    }
    public function ConsumerTransaction($applicationId)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "=", "Demand Collection")
            ->where('status', 1)
            ->orderByDesc('id');
    }
    public function siteInspectionTransaction($applicationId)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "Site Inspection")
            ->where('status', 1)
            ->orderByDesc('id');
    }
    public function getTransByCitizenId($citizenId)
    {
        return WaterTran::where('citizen_id', $citizenId)
            ->where('status', 1)
            ->orderByDesc('id');
    }
    public function getTransNoForConsumer($applicationId, $applicationFor)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', $applicationFor)
            ->where('status', 1);
    }

    /**
     * | Get Transaction Details According to TransactionId
     * | @param 
     */
    public function getTransactionByTransactionNo($transactionNo)
    {
        return WaterTran::select(
            'water_trans.*',
            'water_tran_details.demand_id'
        )
            ->leftjoin('water_tran_details', 'water_tran_details.tran_id', '=', 'water_trans.id')
            ->where('tran_no', $transactionNo)
            ->where('water_trans.status', 1);
        // ->where('water_tran_details.status', 1);
    }

    /**
     * | Enter the default details of the transacton which have 0 Connection charges
     * | @param totalConnectionCharges
     * | @param ulbId
     * | @param req
     * | @param applicationId
     * | @param connectionId
     * | @param connectionType
        | Check for the user Id for wether to save the user id in emp_details_id or in citizen_id
     */
    public function saveZeroConnectionCharg($totalConnectionCharges, $ulbId, $req, $applicationId, $connectionId, $connectionType)
    {
        $user               = auth()->user();
        $refIdGeneration    = new IdGeneration();
        $transactionNo      = $refIdGeneration->generateTransactionNo($ulbId);
        if ($user->user_type == 'Citizen') {
            $isJsk = false;
            $paymentMode = "Online";                                                // Static
            $citizenId = $user->id;
        } else {                                                                    // ($user->user_type != 'Citizen')
            $empId = $user->id;
            $paymentMode = "Cash";                                                  // Static
        }

        $watertransaction = new WaterTran;
        $watertransaction->related_id       = $applicationId;
        $watertransaction->ward_id          = $req->wardId;
        $watertransaction->tran_type        = $connectionType;
        $watertransaction->tran_date        = Carbon::now();
        $watertransaction->payment_mode     = $paymentMode;
        $watertransaction->amount           = $totalConnectionCharges;
        $watertransaction->emp_dtl_id       = $empId ?? null;
        $watertransaction->citizen_id       = $citizenId ?? null;
        $watertransaction->user_type        = $user->user_type;
        $watertransaction->is_jsk           = $isJsk ?? true;
        $watertransaction->created_at       = Carbon::now();
        $watertransaction->ip_address       = getClientIpAddress();
        $watertransaction->ulb_id           = $ulbId;
        $watertransaction->tran_no          = $transactionNo;
        $watertransaction->save();
        $transactionId = $watertransaction->id;

        $mWaterTranDetail = new WaterTranDetail();
        $mWaterTranDetail->saveDefaultTrans($totalConnectionCharges, $applicationId, $transactionId, $connectionId);
    }

    public function chequeTranDtl($ulbId)
    {
        return WaterTran::select(
            'water_trans.*',
            DB::raw("TO_CHAR(water_trans.tran_date, 'DD-MM-YYYY') as tran_date"),
            'water_cheque_dtls.*',
            DB::raw("TO_CHAR(water_cheque_dtls.cheque_date, 'DD-MM-YYYY') as cheque_date"),
            DB::raw("TO_CHAR(water_cheque_dtls.clear_bounce_date, 'DD-MM-YYYY') as clear_bounce_date"),
            'user_name',
            DB::raw("2 as module_id"),
        )
            ->leftjoin('water_cheque_dtls', 'water_cheque_dtls.transaction_id', 'water_trans.id')
            ->join('users', 'users.id', 'water_cheque_dtls.user_id')
            ->whereIn('payment_mode', ['Cheque', 'DD'])
            ->where('water_trans.ulb_id', $ulbId);
    }

    /**
     * | Post Water Transaction
        | Make the column for pg_response_id and pg_id
     */
    public function waterTransaction($req, $consumer)
    {
        $waterTrans = new WaterTran();
        $waterTrans->related_id         = $req['id'];
        $waterTrans->amount             = $req['amount'];
        $waterTrans->tran_type          = $req['chargeCategory'];
        $waterTrans->tran_date          = $req['todayDate'];
        $waterTrans->tran_no            = $req['tranNo'];
        $waterTrans->payment_mode       = $req['paymentMode'];
        $waterTrans->emp_dtl_id         = $req['userId'] ?? null;
        $waterTrans->citizen_id         = $req['citizenId'] ?? null;
        $waterTrans->is_jsk             = $req['isJsk'] ?? false;
        $waterTrans->user_type          = $req['userType'];
        $waterTrans->ulb_id             = $req['ulbId'];
        $waterTrans->ward_id            = $consumer['ward_mstr_id'];
        $waterTrans->due_amount         = $req['leftDemandAmount'] ?? 0;
        $waterTrans->adjustment_amount  = $req['adjustedAmount'] ?? 0;
        $waterTrans->pg_response_id     = $req['pgResponseId'] ?? null;
        $waterTrans->pg_id              = $req['pgId'] ?? null;
        if ($req->penaltyIds) {
            $waterTrans->penalty_ids    = $req->penaltyIds;
            $waterTrans->is_penalty     = $req->isPenalty;
        }
        $waterTrans->save();

        return [
            'id' => $waterTrans->id
        ];
    }

    /**
     * | Water Transaction Details by date
        | Not Used
     */
    public function tranDetail($date, $ulbId)
    {
        return WaterTran::select(
            'users.id',
            'users.user_name',
            DB::raw("sum(amount) as amount"),
        )
            ->join('users', 'users.id', 'water_trans.emp_dtl_id')
            ->where('verified_date', $date)
            ->where('water_trans.status', 1)
            ->where('payment_mode', '!=', 'ONLINE')
            ->where('verify_status', 1)
            ->where('water_trans.ulb_id', $ulbId)
            ->groupBy(["users.id", "users.user_name"]);
    }

    /**
     * | Get Transaction Details for current Date
     * | And for current login user
     */
    public function tranDetailByDate()
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $userType = auth()->user()->user_type;
        $rfTransMode = Config::get("payment-constants.PAYMENT_OFFLINE_MODE.5");

        return WaterTran::where('tran_date', $currentDate)
            ->where('user_type', $userType)
            ->where('payment_mode', '!=', $rfTransMode)
            ->get();
    }

    /**
     * | Save the verify status in case of pending verification
     * | @param watertransId
     */
    public function saveVerifyStatus($watertransId)
    {
        WaterTran::where('id', $watertransId)
            ->update([
                'verify_status' => 2
            ]);
    }

    /**
     * | Get details of online transactions
     * | According to Fyear
     * | @param fromDate
     * | @param toDate
     */
    public function getOnlineTrans($fromDate, $toDate)
    {
        return WaterTran::select('id', 'amount')
            ->where('payment_mode', 'Online')
            ->where('status', 1)
            ->whereBetween('tran_date', [$fromDate, $toDate])
            ->orderByDesc('id');
    }

    /**
     * | Save if the payment is penalty or not 
     * | and the peanlty ids of it 
     */
    public function saveIsPenalty($transactionId, $penaltyIds)
    {
        WaterTran::where('id', $transactionId)
            ->where('status', 1)
            ->update([
                'is_penalty' => 1,
                'penalty_ids' => $penaltyIds
            ]);
    }
}

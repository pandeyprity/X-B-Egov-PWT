<?php

namespace App\Models\Payment;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TempTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function tempTransaction($req)
    {
        $mTempTransaction = new TempTransaction();
        $mTempTransaction->create($req);
    }

    public function transactionList($date, $userId, $ulbId)
    {
        return TempTransaction::select(
            'temp_transactions.id',
            'transaction_no as tran_no',
            'payment_mode',
            'cheque_dd_no',
            'bank_name',
            'amount',
            'module_id',
            'ward_no as ward_name',
            'application_no',
            DB::raw("TO_CHAR(tran_date, 'DD-MM-YYYY') as tran_date"),
            'name as user_name',
            'users.id as tc_id'
        )
            ->join('users', 'users.id', 'temp_transactions.user_id')
            ->where('payment_mode', '!=', 'ONLINE')
            ->where('tran_date', $date)
            ->where('user_id', $userId)
            ->where('temp_transactions.ulb_id', $ulbId)
            ->get();
    }

    public function transactionDtl($date, $ulbId)
    {
        return TempTransaction::select('*')
            ->leftjoin('users', 'users.id', 'temp_transactions.user_id')
            ->where('tran_date', $date)
            ->where('temp_transactions.ulb_id', $ulbId);
    }
}

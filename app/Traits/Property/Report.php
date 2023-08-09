<?php

namespace App\Traits\Property;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 22-03-2023 
 * | Created By - Mrinal Kumar
 */
trait Report
{
    public function gbSafCollectionQuery($table, $fromDate, $uptoDate, $officerTbl)
    {
        return DB::table($table)
            ->select(
                't.id',
                DB::raw("'gbsaf' as type"),
                'pp.id as property_id',
                'pp.holding_no',
                'gbo.officer_name as owner_name',
                'mobile_no',
                'ward_name as ward_no',
                $table . '.saf_no',
                $table . '.ward_mstr_id',
                $table . '.prop_address',
                'tran_date',
                'payment_mode as transaction_mode',
                't.user_id as tc_id',
                'user_name as emp_name',
                'tran_no',
                'cheque_no',
                'bank_name',
                'branch_name',
                'amount',
                DB::raw("sum(amount) as total_amount"),
                DB::raw("CONCAT (from_fyear,'(',from_qtr,')','/',to_fyear,'(',to_qtr,')') AS from_upto_fy_qtr"),
            )
            ->join('prop_transactions as t', 't.saf_id', $table . '.id')
            ->join($officerTbl . ' as gbo', 'gbo.saf_id', $table . '.id')
            ->leftjoin('prop_properties as pp', 'pp.id', 't.property_id')
            ->join('users', 'users.id', 't.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', $table . '.ward_mstr_id')
            ->leftJoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 't.id')
            ->where($table . '.is_gb_saf', true)
            ->whereBetween('tran_date', [$fromDate, $uptoDate])
            ->groupBy(
                't.id',
                'pp.id',
                'gbo.officer_name',
                'gbo.mobile_no',
                'ulb_ward_masters.ward_name',
                'users.user_name',
                'prop_cheque_dtls.cheque_no',
                'bank_name',
                'branch_name',
                $table . '.saf_no',
                $table . '.ward_mstr_id',
                $table . '.prop_address'
            );
    }
}

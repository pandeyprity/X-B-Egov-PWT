<?php

namespace App\Models\Bandobastee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BdPayment extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**
     * | Make Request data for store
     */
    public function metaReqs($req)
    {
        return [
            'settler_id' => $req->settlerId,
            'ulb_id' => $req->ulbId,
            'installment_date' => $req->installmentDate,
            'installment_amount' => $req->installmentAmount,
        ];
    }

    /**
     * | Add installment payment
     */
    public function installmentPayment($req)
    {
        $metaReqs = $this->metaReqs($req);
        return BdPayment::create($metaReqs);
    }

    /**
     * | Get installment mayment list
     */
    public function listInstallmentPayment($settlerId)
    {
        return BdPayment::select('installment_amount', DB::raw('cast(installment_date as date) as installment_date'))
            ->where('settler_id', $settlerId)
            ->get();
    }

    /**
     * | Count total paid installment
     */
    public function totalInstallment($id)
    {
        return DB::table('bd_payments')->select(DB::raw('sum(installment_amount) as installment_amount'))->where('settler_id', $id)->first()->installment_amount;
    }
}

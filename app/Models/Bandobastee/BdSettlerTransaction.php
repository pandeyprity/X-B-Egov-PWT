<?php

namespace App\Models\Bandobastee;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BdSettlerTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * | Make meta request for store records
     */
    public function metaReqs($req)
    {
        return [
            'settler_id' => $req->settlerId,
            'amount' => $req->amount,
            'is_penalty' => $req->isPenalty,
            'remarks' => $req->remarks,
            'penalty_type' => $req->penaltyType,
            'ulb_id' => $req->ulbId,
            'date' => Carbon::now()->format('Y-m-d'),
        ];
    }

    /**
     * | Add penalty and performence security money
     */
    public function addTransaction($req)
    {
        $metaReqs = $this->metaReqs($req);
        return BdSettlerTransaction::create($metaReqs);
    }

    /**
     * | List Settler Transaction
     */
    public function listSettlerTransaction($settlerId)
    {
        return BdSettlerTransaction::select('id', 'amount', DB::raw('cast(date as date) as date'), 'is_penalty', 'penalty_type', 'remarks')
            ->where('settler_id', $settlerId)
            ->orderBy('id', 'ASC')
            ->get();
    }
    /**
     * | Get perforamnce security money and penalty list
     */
    public function performanceSecurity($id, $type)
    {
        return DB::table('bd_settler_transactions')->select(DB::raw('sum(amount) as amount'))->where('settler_id', $id)->where('is_penalty', $type)->first()->amount;
    }
}

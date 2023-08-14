<?php

namespace App\Models\Param;

use App\Models\Advertisements\AdvSelfadvertisement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvMarTransaction extends Model
{
    use HasFactory;
    /**
     * | Add every transaction in transaction table
     */
    public function addTransaction($req, $moduleId, $moduleType, $paymentMode)
    {
        $addData = new AdvMarTransaction();

        $addData->module_id         = $moduleId;
        $addData->workflow_id       = $req->workflow_id;
        $addData->application_id    = $req->id;
        $addData->module_type       = $moduleType;
        $addData->transaction_id    = $req->payment_id;
        $addData->transaction_no    = "$req->id-" . time();
        $addData->transaction_date  = $req->payment_date;
        $addData->amount            = $req->payment_amount;
        if (isset($req->demand_amount)) {
            $addData->demand_amount     = $req->demand_amount;
        }
        $addData->payment_details   = $req->payment_details;
        $addData->payment_mode      = $paymentMode;
        if (isset($req->entity_ward_id)) {
            $addData->entity_ward_id    = $req->entity_ward_id;
        }
        $addData->ulb_id            = $req->ulb_id;
        $addData->verify_date       = Carbon::now();
        $addData->verify_status     = 1;
        $addData->save();
    }
}

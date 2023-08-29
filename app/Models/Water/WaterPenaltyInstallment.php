<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPenaltyInstallment extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * |----------------------------- Save new water -----------------------------|
     * | @param 
     * | @var
     */
    public function saveWaterPenelty($applicationId, $installments, $connectionFor, $connectionId, $consumerId)
    {
        $quaters = new WaterPenaltyInstallment();
        $quaters->apply_connection_id   = $applicationId ?? null;
        $quaters->installment_amount    = $installments['installment_amount'];
        $quaters->penalty_head          = $installments['penalty_head'];
        $quaters->balance_amount        = $installments['balance_amount'];
        $quaters->payment_from          = $connectionFor;
        $quaters->related_demand_id     = $connectionId;
        $quaters->consumer_id           = $consumerId ?? null;
        $quaters->save();
    }

    /**
     * |------------- Delete the Penelty Installment -------------------|
        | Soft Deleta the data
     */
    public function deleteWaterPenelty($applicationId)
    {
        $waterPenelty = WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->get();
        if ($waterPenelty) {
            WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
                ->delete();
        }
    }


    /**
     * | Get the penalty installment according to application Id
     * | @param applicationId
     */
    public function getPenaltyByApplicationId($applicationId)
    {
        return WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->where('status', 1);
    }


    /**
     * | Deactivate the water penalty charges
     * | @param request
     */
    public function deactivatePenalty($applicationId)
    {
        WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->update([
                'status' => false
            ]);
    }


    /**
     * | Update penalty Payment Status true in the payment
     * | @param req
     */
    public function updatePenaltyPayment($penaltyId)
    {
        WaterPenaltyInstallment::whereIn('id', $penaltyId)
            ->update([
                'paid_status' => 1
            ]);
    }

    /**
     * | Get Penalty details by Penalty id 
     * | the penalty id must be in array 
     * | @param penaltyId
     */
    public function getPenaltyByArrayOfId($penaltyIds)
    {
        return WaterPenaltyInstallment::whereIn('id', $penaltyIds)
            ->where('status', 1)
            ->get();
    }


    /**
     * | Deactivate the penalty
     * | In case of site inspection change the old penalty status to 2
     * | @param 
     * | @param 
     */
    public function deactivateOldPenalty($request, $applicationId, $chargeCatagory)
    {
        WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->where('payment_from', '!=', $chargeCatagory['SITE_INSPECTON'])
            ->where('paid_status', 0)
            ->update([
                'status' => 2
            ]);
    }

    /**
     * | Deactivate site inspection penalty
     * | @param 
     */
    public function deactivateSitePenalty($applicationId, $siteInspection)
    {
        WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->where('payment_from', $siteInspection)
            ->update([
                'status' => false
            ]);
    }


    /**
     * | Get penalty details accordin to charge Id
     * | @param chargeId
     */
    public function getPenaltyByChargeId($chargeId)
    {
        return WaterPenaltyInstallment::where('related_demand_id', $chargeId)
            ->where('paid_status', 0)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | save the penalty payment status using penalty ids
     */
    public function savePenaltyStatusByIds($penaltyIds, $status)
    {
        WaterPenaltyInstallment::whereIn('id', $penaltyIds)
            ->where('status', 1)
            ->update([
                'paid_status' => $status
            ]);
    }
}

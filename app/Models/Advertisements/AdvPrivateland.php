<?php

namespace App\Models\Advertisements;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvPrivateland extends Model
{
    use HasFactory;


    /**
     * | Get Application All Approve List
     */
    public function allApproveList()
    {
        return AdvPrivateland::select(
            'adv_privatelands.id',
            'adv_privatelands.application_no',
            'adv_privatelands.applicant',
            'adv_privatelands.applicant as owner_name',
            DB::raw("TO_CHAR(adv_privatelands.application_date, 'DD-MM-YYYY') as application_date"),
            'adv_privatelands.application_type',
            'adv_privatelands.entity_name',
            'adv_privatelands.mobile_no',
            'adv_privatelands.entity_address',
            'adv_privatelands.payment_amount',
            'adv_privatelands.payment_status',
            'adv_privatelands.valid_upto',
            'adv_privatelands.valid_from',
            'adv_privatelands.approve_date',
            'adv_privatelands.citizen_id',
            'adv_privatelands.user_id',
            'adv_privatelands.ulb_id',
            'adv_privatelands.workflow_id',
            'adv_privatelands.license_no',
            DB::raw("'privateLand' as type"),
            'um.ulb_name as ulb_name',
        )
            ->join('ulb_masters as um', 'um.id', '=', 'adv_privatelands.ulb_id')
            ->orderByDesc('adv_privatelands.id')
            ->get();
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listApproved($citizenId, $userType)
    {
        $allApproveList = $this->allApproveList();
        foreach ($allApproveList as $key => $list) {
            $current_date = carbon::now()->format('Y-m-d');
            $notify_date = carbon::parse($list['valid_upto'])->subDay(30)->format('Y-m-d');
            if ($current_date >= $notify_date) {
                $allApproveList[$key]['renew_option'] = '1';     // Renew option Show
            }
            if ($current_date < $notify_date) {
                $allApproveList[$key]['renew_option'] = '0';      // Renew option Not Show
            }
            if ($list['valid_upto'] < $current_date) {
                $allApproveList[$key]['renew_option'] = 'Expired';    // Renew Expired
            }
        }
        if ($userType == 'Citizen') {
            return  collect($allApproveList->where('citizen_id', $citizenId))->values();
        } else {
            return collect($allApproveList)->values();
        }
    }


    /**
     * | Get Application Approve List by Role Ids
     */
    public function listjskApprovedApplication($userId)
    {
        return AdvPrivateland::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'payment_amount',
                'approve_date',
            )
            ->orderByDesc('id')
            ->get();
    }


    /**
     * | Get Application Details FOr Payments
     */
    public function getApplicationDetailsForPayment($id)
    {
        return AdvPrivateland::where('id', $id)
            ->select(
                'id',
                'applicant',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'entity_name',
                'payment_status',
                'payment_amount',
                'approve_date',
                'ulb_id',
                'workflow_id',
            )
            ->first();
    }

    /**
     * | Get Payment Details
     */
    public function getPaymentDetails($paymentId)
    {
        $details = AdvPrivateland::select(
            'adv_privatelands.payment_amount',
            'adv_privatelands.payment_id',
            'adv_privatelands.payment_date',
            'adv_privatelands.entity_address as address',
            'adv_privatelands.applicant',
            'adv_privatelands.payment_details',
            'adv_privatelands.application_no',
            'adv_privatelands.license_no',
            'adv_privatelands.license_from as valid_from',
            'adv_privatelands.license_to as valid_upto',
            'adv_privatelands.holding_no',
            'adv_privatelands.payment_mode',
            'adv_privatelands.application_date as applyDate',
            'adv_privatelands.trade_license_no',
            'ulb_masters.ulb_name as ulbName',
            'ulb_masters.logo as ulbLogo',
            'wn.ward_name as wardNo',
            DB::raw("'Advertisement' as module"),
        )
            ->leftjoin('ulb_masters', 'adv_privatelands.ulb_id', '=', 'ulb_masters.id')
            ->leftjoin('ulb_ward_masters as wn', DB::raw('adv_privatelands.entity_ward_id::int'), '=', 'wn.id')
            ->where('adv_privatelands.payment_id', $paymentId)
            ->first();
        $details->payment_details = json_decode($details->payment_details);
        $details->towards = "Private Land";
        $details->payment_date = Carbon::createFromFormat('Y-m-d H:i:s', $details->payment_date)->format('d-m-Y');
        $details->valid_from = Carbon::createFromFormat('Y-m-d',  $details->valid_from)->format('d-m-Y');
        $details->valid_upto = Carbon::createFromFormat('Y-m-d',  $details->valid_upto)->format('d-m-Y');
        $details->applyDate = Carbon::createFromFormat('Y-m-d',  $details->applyDate)->format('d-m-Y');
        return $details;
    }

    /**
     * | Paid Payment by cash
     */
    public function paymentByCash($req)
    {

        if ($req->status == '1') {
            // Self Privateland Table Update
            $mAdvPrivateland = AdvPrivateland::find($req->applicationId);        // Application ID
            $mAdvPrivateland->payment_status = $req->status;
            $mAdvPrivateland->payment_mode = "Cash";
            $pay_id = $mAdvPrivateland->payment_id = "Cash-$req->applicationId-" . time();
            // $mAdvCheckDtls->remarks = $req->remarks;
            $mAdvPrivateland->payment_date = Carbon::now();
            // $mAdvPrivateland->payment_details = "By Cash";

            $payDetails = array('paymentMode' => 'Cash', 'id' => $req->applicationId, 'amount' => $mAdvPrivateland->payment_amount, 'demand_amount' => $mAdvPrivateland->demand_amount, 'workflowId' => $mAdvPrivateland->workflow_id, 'userId' => $mAdvPrivateland->citizen_id, 'ulbId' => $mAdvPrivateland->ulb_id, 'transDate' => Carbon::now(), 'paymentId' => $pay_id);

            $mAdvPrivateland->payment_details = json_encode($payDetails);
            if ($mAdvPrivateland->renew_no == NULL) {
                $mAdvPrivateland->valid_from = Carbon::now();
                $mAdvPrivateland->valid_upto = Carbon::now()->addYears(1)->subDay(1);
            } else {
                $previousApplication = $this->findPreviousApplication($mAdvPrivateland->application_no);
                $mAdvPrivateland->valid_from = $previousApplication->valid_upto;
                $mAdvPrivateland->valid_upto = Carbon::createFromFormat('Y-m-d', $previousApplication->valid_upto)->addYears(1)->subDay(1);
            }
            $mAdvPrivateland->save();
            $renewal_id = $mAdvPrivateland->last_renewal_id;


            // Privateland Renewal Table Updation
            $mAdvPrivatelandRenewal = AdvPrivatelandRenewal::find($renewal_id);
            $mAdvPrivatelandRenewal->payment_status = 1;
            $mAdvPrivatelandRenewal->payment_mode = "Cash";
            $mAdvPrivatelandRenewal->payment_id =  $pay_id;
            $mAdvPrivatelandRenewal->payment_date = Carbon::now();
            $mAdvPrivatelandRenewal->payment_amount = $mAdvPrivateland->payment_amount;
            $mAdvPrivatelandRenewal->demand_amount = $mAdvPrivateland->demand_amount;
            $mAdvPrivatelandRenewal->valid_from =  $mAdvPrivateland->valid_from;
            $mAdvPrivatelandRenewal->valid_upto = $mAdvPrivateland->valid_upto;
            $mAdvPrivatelandRenewal->payment_details = json_encode($payDetails);
            $status = $mAdvPrivatelandRenewal->save();
            $returnData['status'] = $status;
            $returnData['payment_id'] = $pay_id;
            return $returnData;
        }
    }

    // Find Previous Payment Date
    public function findPreviousApplication($application_no)
    {
        return $details = AdvPrivatelandRenewal::select('valid_upto')
            ->where('application_no', $application_no)
            ->orderByDesc('id')
            ->skip(1)->first();
    }

    /**
     * | Get Application details for renew
     */
    public function applicationDetailsForRenew($appId)
    {
        $details = AdvPrivateland::select(
            'adv_privatelands.*',
            'adv_privatelands.typology as typology_id',
            'adv_privatelands.zone as zone_id',
            'adv_privatelands.display_type as display_type_id',
            'adv_privatelands.installation_location as installation_location_id',
            'il.string_parameter as installation_location',
            'dt.string_parameter as display_type',
            'typo.descriptions as typology',
            'w.ward_name',
            'pw.ward_name as permanent_ward_name',
            'ew.ward_name as entity_ward_name',
            'ulb.ulb_name',
        )
            ->leftJoin('ref_adv_paramstrings as il', 'il.id', '=', DB::raw('adv_privatelands.installation_location::int'))
            ->leftJoin('adv_typology_mstrs as typo', 'typo.id', '=', 'adv_privatelands.typology')
            ->leftJoin('ref_adv_paramstrings as dt', 'dt.id', '=', DB::raw('adv_privatelands.display_type::int'))
            ->leftJoin('ulb_ward_masters as w', 'w.id', '=', DB::raw('adv_privatelands.ward_id::int'))
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', DB::raw('adv_privatelands.permanent_ward_id::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', DB::raw('adv_privatelands.entity_ward_id::int'))
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', DB::raw('adv_privatelands.ulb_id::int'))
            ->where('adv_privatelands.id', $appId)->first();
        if (!empty($details)) {
            $mWfActiveDocument = new WfActiveDocument();
            $documents = $mWfActiveDocument->uploadDocumentsViewById($appId, $details->workflow_id);
            $details['documents'] = $documents;
        }
        return $details;
    }

    /**
     * | Get Reciept Details 
     */
    public function getApprovalLetter($applicationId)
    {
        $recieptDetails = AdvPrivateland::select(
            'adv_privatelands.approve_date',
            'adv_privatelands.applicant as applicant_name',
            'adv_privatelands.application_no',
            'adv_privatelands.license_no',
            'adv_privatelands.payment_date as license_start_date',
            DB::raw('CONCAT(application_date,id) AS reciept_no')
        )
            ->where('adv_privatelands.id', $applicationId)
            ->first();
        // $recieptDetails->payment_details=json_decode($recieptDetails->payment_details);
        return $recieptDetails;
    }

    /**
     * | Approve List For Report
     */
    public function approveListForReport()
    {
        return AdvPrivateland::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'ulb_id', 'display_type', DB::raw("'Approve' as application_status"));
    }
}

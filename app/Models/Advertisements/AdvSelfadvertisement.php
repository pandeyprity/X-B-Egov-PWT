<?php

namespace App\Models\Advertisements;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvSelfadvertisement extends Model
{
    use HasFactory;

    public function allApproveList()
    {
        return AdvSelfadvertisement::select(
            'adv_selfadvertisements.id',
            'adv_selfadvertisements.application_no',
            DB::raw("TO_CHAR(adv_selfadvertisements.application_date, 'DD-MM-YYYY') as application_date"),
            'adv_selfadvertisements.applicant',
            'adv_selfadvertisements.applicant as owner_name',
            'adv_selfadvertisements.entity_name',
            'adv_selfadvertisements.entity_ward_id',
            'adv_selfadvertisements.mobile_no',
            'adv_selfadvertisements.entity_address',
            'adv_selfadvertisements.payment_status',
            'adv_selfadvertisements.payment_amount',
            'adv_selfadvertisements.approve_date',
            'adv_selfadvertisements.ulb_id',
            'adv_selfadvertisements.workflow_id',
            'adv_selfadvertisements.citizen_id',
            'adv_selfadvertisements.license_no',
            'adv_selfadvertisements.valid_upto',
            'adv_selfadvertisements.valid_from',
            'adv_selfadvertisements.user_id',
            'adv_selfadvertisements.application_type',
            DB::raw("'selfAdvt' as type"),
            DB::raw("'Approved' as applicationStatus"),
            'um.ulb_name as ulb_name',
        )
            ->join('ulb_masters as um', 'um.id', '=', 'adv_selfadvertisements.ulb_id')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listApproved($citizenId, $userType)
    {
        $allApproveList = $this->allApproveList();
        foreach ($allApproveList as $key => $list) {
            $activeSelf = AdvActiveSelfadvertisement::where('application_no', $list['application_no'])->count();
            $current_date = carbon::now()->format('Y-m-d');
            $notify_date = carbon::parse($list['valid_upto'])->subDay(30)->format('Y-m-d');
            if ($current_date >= $notify_date) {
                if ($activeSelf == 0) {
                    $allApproveList[$key]['renew_option'] = '1';     // Renew option Show
                } else {
                    $allApproveList[$key]['renew_option'] = '0';     // Already Renew
                }
            }
            if ($current_date < $notify_date) {
                $allApproveList[$key]['renew_option'] = '0';      // Renew option Not Show
            }
            if ($list['valid_upto'] < $current_date) {
                $allApproveList[$key]['renew_option'] = 'Expired';    // Renew Expired
            }
        }
        if ($userType == 'Citizen') {
            return collect($allApproveList->where('citizen_id', $citizenId))->values();
        } else {
            return collect($allApproveList)->values();
        }
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listJskApprovedApplication($userId)
    {
        return AdvSelfadvertisement::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'payment_status',
                'payment_amount',
                'approve_date',
                'license_no',
                'ulb_id',
                'workflow_id',
            )
            ->orderByDesc('id')
            ->get();
    }


    /**
     * | Get Application Details For Payments
     */
    public function applicationDetailsForPayment($id)
    {
        return AdvSelfadvertisement::where('id', $id)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'applicant',
                'entity_name',
                'entity_address',
                'payment_status',
                'payment_amount',
                'license_no',
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
        $details = AdvSelfadvertisement::select(
            'adv_selfadvertisements.payment_amount',
            'adv_selfadvertisements.payment_id',
            'adv_selfadvertisements.payment_date',
            'adv_selfadvertisements.license_no',
            'adv_selfadvertisements.application_no',
            'adv_selfadvertisements.entity_address as address',
            'adv_selfadvertisements.applicant',
            'adv_selfadvertisements.payment_details',
            'adv_selfadvertisements.valid_from',
            'adv_selfadvertisements.valid_upto',
            'adv_selfadvertisements.payment_mode',
            'adv_selfadvertisements.holding_no',
            'adv_selfadvertisements.application_date as applyDate',
            'adv_selfadvertisements.trade_license_no',
            'ulb_masters.ulb_name as ulbName',
            'ulb_masters.logo as ulbLogo',
            'ly.string_parameter as licenseYear',
            'wn.ward_name as wardNo',
            DB::raw("'Advertisement' as module"),
        )
            ->leftjoin('ulb_masters', 'adv_selfadvertisements.ulb_id', '=', 'ulb_masters.id')
            ->leftjoin('ref_adv_paramstrings as ly', 'adv_selfadvertisements.license_year', '=', 'ly.id')
            ->leftjoin('ulb_ward_masters as wn', 'adv_selfadvertisements.ward_id', '=', 'wn.id')
            ->where('adv_selfadvertisements.payment_id', $paymentId)
            ->first();
        $details->payment_details = json_decode($details->payment_details);
        $details->towards = "Self Advertisement";
        $details->payment_date = Carbon::createFromFormat('Y-m-d', $details->payment_date)->format('d-m-Y');
        $details->valid_from = Carbon::createFromFormat('Y-m-d', $details->valid_from)->format('d-m-Y');
        $details->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->format('d-m-Y');
        $details->applyDate = Carbon::createFromFormat('Y-m-d',  $details->applyDate)->format('d-m-Y');
        return $details;
    }
    /**
     * | Payment By Cash
     */
    public function paymentByCash($req)
    {
        if ($req->status == '1') {
            // Self Advertisement Table Update
            $mAdvSelfadvertisement = AdvSelfadvertisement::find($req->applicationId);
            $mAdvSelfadvertisement->payment_status = $req->status;
            $pay_id = $mAdvSelfadvertisement->payment_id = "Cash-$req->applicationId-" . time();
            $mAdvSelfadvertisement->payment_date = Carbon::now();
            $mAdvSelfadvertisement->payment_mode = "Cash";

            $payDetails = array('paymentMode' => 'Cash', 'id' => $req->applicationId, 'amount' => $mAdvSelfadvertisement->payment_amount, 'demand_amount' => $mAdvSelfadvertisement->demand_amount, 'workflowId' => $mAdvSelfadvertisement->workflow_id, 'userId' => $mAdvSelfadvertisement->citizen_id, 'ulbId' => $mAdvSelfadvertisement->ulb_id, 'transDate' => Carbon::now(), 'paymentId' => $pay_id);

            $mAdvSelfadvertisement->payment_details = json_encode($payDetails);
            if ($mAdvSelfadvertisement->renew_no == NULL) {                             // Fresh Application Time 
                $mAdvSelfadvertisement->valid_from = Carbon::now();
                $mAdvSelfadvertisement->valid_upto = Carbon::now()->addYears(1)->subDay(1);
            } else {                                                              // Renewal Application Time 
                $previousApplication = $this->findPreviousApplication($mAdvSelfadvertisement->license_no);
                $mAdvSelfadvertisement->valid_from = $previousApplication->valid_upto;
                $mAdvSelfadvertisement->valid_upto = Carbon::createFromFormat('Y-m-d', $previousApplication->valid_upto)->addYears(1)->subDay(1);
            }
            $mAdvSelfadvertisement->save();
            $renewal_id = $mAdvSelfadvertisement->last_renewal_id;

            // Renewal Table Updation
            $mAdvSelfAdvertRenewal = AdvSelfadvetRenewal::find($renewal_id);
            $mAdvSelfAdvertRenewal->payment_status = 1;
            $mAdvSelfAdvertRenewal->payment_id =  $pay_id;
            $mAdvSelfAdvertRenewal->payment_date = Carbon::now();
            $mAdvSelfAdvertRenewal->payment_mode = "Cash";
            $mAdvSelfAdvertRenewal->payment_amount =  $mAdvSelfadvertisement->payment_amount;
            $mAdvSelfAdvertRenewal->demand_amount =  $mAdvSelfadvertisement->demand_amount;
            $mAdvSelfAdvertRenewal->valid_from = $mAdvSelfadvertisement->valid_from;
            $mAdvSelfAdvertRenewal->valid_upto = $mAdvSelfadvertisement->valid_upto;
            $mAdvSelfAdvertRenewal->payment_details = json_encode($payDetails);
            $status = $mAdvSelfAdvertRenewal->save();
            $returnData['status'] = $status;
            $returnData['payment_id'] = $pay_id;
            return $returnData;
        }
    }

    /**
     * | Get Previous application valid date for renewal
     */
    public function findPreviousApplication($license_no)
    {
        return  AdvSelfadvetRenewal::select('valid_upto')
            ->where('license_no', $license_no)
            ->orderByDesc('id')
            ->skip(1)->first();
    }


    /**
     * | Get Application Details for Renew
     */
    public function applicationDetailsForRenew($appId)
    {
        $details = AdvSelfadvertisement::select(
            'adv_selfadvertisements.*',
            'adv_selfadvertisements.license_year as license_year_id',
            'adv_selfadvertisements.installation_location as installation_location_id',
            'ly.string_parameter as license_year_name',
            'ew.ward_name as entity_ward_name',
            'il.string_parameter as installation_location_name',
            'w.ward_name',
            'pw.ward_name as permanent_ward_name',
            'cat.type as advt_category_name',
            'ulb.ulb_name',
        )
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('adv_selfadvertisements.license_year::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', DB::raw('adv_selfadvertisements.entity_ward_id::int'))
            ->leftJoin('ref_adv_paramstrings as il', 'il.id', '=', DB::raw('adv_selfadvertisements.installation_location::int'))
            ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'adv_selfadvertisements.ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'adv_selfadvertisements.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'adv_selfadvertisements.ulb_id')
            ->leftJoin('adv_selfadv_categories as cat', 'cat.id', '=', 'adv_selfadvertisements.advt_category')
            ->where('adv_selfadvertisements.id', $appId)->first();
        if (!empty($details)) {
            $mWfActiveDocument = new WfActiveDocument();
            $documents = $mWfActiveDocument->uploadDocumentsViewById($appId, $details->workflow_id);
            $details['documents'] = $documents;
        }
        return $details;
    }

    /**
     * | Search Application by Name or Mobile 
     */
    public function searchByNameorMobile($req)
    {
        $list = AdvSelfadvertisement::select('adv_agencies.*', 'et.string_parameter as entityType', 'adv_agencies.entity_type as entity_type_id')
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'adv_agencies.entity_type');
        if ($req->filterBy == 'mobileNo') {
            $filterList = $list->where('adv_agencies.mobile_no', $req->parameter);
        }
        if ($req->filterBy == 'entityName') {
            $filterList = $list->where('adv_agencies.entity_name', $req->parameter);
        }
        return $filterList->get();
    }

    /**
     * | Get Reciept Details 
     */
    public function getApprovalLetter($applicationId)
    {
        $recieptDetails = AdvSelfadvertisement::select(
            'adv_selfadvertisements.approve_date',
            // DB::raw('CONVERT(date, adv_selfadvertisements.approve_date, 105) as approve_date'),
            'adv_selfadvertisements.applicant as applicant_name',
            'adv_selfadvertisements.application_no',
            'adv_selfadvertisements.license_no',
            'adv_selfadvertisements.payment_date as license_start_date',
            DB::raw('case when adv_selfadvertisements.payment_date is NULL then adv_selfadvertisements.approve_date END as license_start_date'),
            DB::raw('CONCAT(application_date,id) AS reciept_no')
        )
            ->where('adv_selfadvertisements.id', $applicationId)
            ->first();
        // $recieptDetails->payment_details=json_decode($recieptDetails->payment_details);
        return $recieptDetails;
    }

    /**
     * | Get Approve list for Report
     */
    public function allApproveListForReport()
    {
        return AdvSelfadvertisement::select(
            'id',
            'application_no',
            'application_date',
            'applicant',
            'applicant as owner_name',
            'entity_name',
            'entity_ward_id',
            'mobile_no',
            'entity_address',
            'payment_status',
            'payment_amount',
            'approve_date',
            'ulb_id',
            'workflow_id',
            'citizen_id',
            'license_no',
            'valid_upto',
            'valid_from',
            'user_id',
            'application_type',
            DB::raw("'selfAdvt' as type"),
            DB::raw("'Approved' as applicationStatus"),
        )
            ->orderByDesc('id')->get();
    }

    /**
     * | Get Approve Application List For Report
     */
    public function approveListForReport()
    {
        return AdvSelfadvertisement::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'ulb_id', 'license_year', 'display_type', DB::raw("'Approve' as application_status"));
    }
}

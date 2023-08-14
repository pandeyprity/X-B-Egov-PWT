<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class AdvAgency extends Model
{
    use HasFactory;

    /**
     * | Get Agency Details by Agency Id
     */
    public function getagencyDetails($email)
    {
        $details1 = DB::table('adv_agencies')
            ->select('adv_agencies.*', 'et.string_parameter as entity_type_name')
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', 'adv_agencies.entity_type')
            ->where('adv_agencies.email', $email)
            ->first();
        $details = json_decode(json_encode($details1), true);
        if (!empty($details)) {
            $details['expiry_date'] = $details['valid_upto'];
            $warning_date = carbon::parse($details['valid_upto'])->subDay(30)->format('Y-m-d');;
            $details['warning_date'] = $warning_date;
            $current_date = date('Y-m-d');
            if ($current_date < $warning_date) {
                $details['warning'] = 0; // Warning Not Enabled
            } elseif ($current_date >= $warning_date) {
                $details['warning'] = 1; // Warning Enabled
            }
            if ($current_date > $details['expiry_date']) {
                $details['warning'] = 2;  // Expired
            }
            $directors = DB::table('adv_active_agencydirectors')
                ->select(
                    'adv_active_agencydirectors.*',
                    DB::raw("CONCAT(adv_active_agencydirectors.relative_path,'/',adv_active_agencydirectors.doc_name) as document_path")
                )
                ->where('agency_id', $details['id'])
                ->get();
            $details['directors'] = remove_null($directors->toArray());
        }
        return $details;
    }

    /**
     * Summary of allApproveList
     * @return void
     */
    public function allApproveList()
    {
        return AdvAgency::select(
            'adv_agencies.id',
            'adv_agencies.application_no',
            DB::raw("TO_CHAR(adv_agencies.application_date, 'DD-MM-YYYY') as application_date"),
            'adv_agencies.application_type',
            'adv_agencies.entity_name',
            'adv_agencies.payment_status',
            'adv_agencies.mobile_no',
            'adv_agencies.payment_amount',
            'adv_agencies.approve_date',
            'adv_agencies.valid_upto',
            'adv_agencies.valid_from',
            'adv_agencies.citizen_id',
            'adv_agencies.user_id',
            'adv_agencies.ulb_id',
            'adv_agencies.workflow_id',
            'adv_agencies.license_no',
            DB::raw("'agency' as type"),
            DB::raw("'' as owner_name"),
            'um.ulb_name as ulb_name',
        )
            ->join('ulb_masters as um', 'um.id', '=', 'adv_agencies.ulb_id')
            ->orderByDesc('adv_agencies.id')
            ->get();
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listApproved($citizenId, $userType)
    {
        $allApproveList = $this->allApproveList();
        if ($userType == 'Citizen') {
            return collect($allApproveList)->where('citizen_id', $citizenId)->values();
        } else {
            return collect($allApproveList)->values();
        }
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listjskApprovedApplication($userId)
    {
        return AdvAgency::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
                'payment_status',
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
        return AdvAgency::where('id', $id)
            ->select(
                'id',
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
     * | Check Login User is Asgency or Not
     */
    public function checkAgency($citizenId)
    {
        $details = AdvAgency::where('citizen_id', $citizenId)->select('*')
            ->first();
        $details = json_decode(json_encode($details), true);
        if (!empty($details)) {
            $temp_id = $details['id'];
            // Convert Std Class to Array
            $directors = DB::table('adv_active_agencydirectors')
                ->select(
                    'adv_active_agencydirectors.*',
                    DB::raw("CONCAT(adv_active_agencydirectors.relative_path,'/',adv_active_agencydirectors.doc_name) as document_path")
                )
                ->where('agency_id', $temp_id)
                ->get();
            $details['directors'] = remove_null($directors->toArray());
        }
        return $details;
    }

    /**
     * | Get payment details
     */
    public function getPaymentDetails($paymentId)
    {
        $details = AdvAgency::select(
            'adv_agencies.payment_amount',
            'adv_agencies.payment_id',
            'adv_agencies.payment_date',
            'adv_agencies.address',
            'adv_agencies.entity_name',
            'adv_agencies.application_no',
            'adv_agencies.license_no',
            'adv_agencies.payment_details',
            'adv_agencies.payment_mode',
            'adv_agencies.valid_from',
            'adv_agencies.valid_upto',
            'et.string_parameter as entityType',
            'adv_agencies.application_date as applyDate',
            'ulb_masters.ulb_name as ulbName',
            'ulb_masters.logo as ulbLogo',
            DB::raw("'Advertisement' as module")
        )
            ->leftjoin('ulb_masters', 'adv_agencies.ulb_id', '=', 'ulb_masters.id')
            ->leftjoin('ref_adv_paramstrings as et', 'adv_agencies.entity_type', '=', 'et.id')
            ->where('adv_agencies.payment_id', $paymentId)
            ->first();
        $details->payment_details = json_decode($details->payment_details);
        $details->towards = "Agency";
        $details->payment_date = Carbon::createFromFormat('Y-m-d H:i:s',  $details->payment_date)->format('d-m-Y');
        $details->valid_from = Carbon::createFromFormat('Y-m-d',  $details->valid_from)->format('d-m-Y');
        $details->valid_upto = Carbon::createFromFormat('Y-m-d',  $details->valid_upto)->format('d-m-Y');
        $details->applyDate = Carbon::createFromFormat('Y-m-d',  $details->applyDate)->format('d-m-Y');
        return $details;
    }

    /**
     * | Search application by name or mobile no
     */
    public function searchByNameorMobile($req)
    {
        $list = AdvAgency::select(
            'adv_agencies.*',
            'et.string_parameter as entityType',
            'adv_agencies.entity_type as entity_type_id',
            DB::raw("'Agency' as workflow_name")
        )
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
     * | Payment via Cash
     */
    public function paymentByCash($req)
    {

        if ($req->status == '1') {
            // Agency Table Update
            $mAdvAgency = AdvAgency::find($req->applicationId);
            $mAdvAgency->payment_status = $req->status;
            $mAdvAgency->payment_mode = "Cash";
            $pay_id = $mAdvAgency->payment_id = "Cash-$req->applicationId-" . time();
            // $mAdvCheckDtls->remarks = $req->remarks;
            $mAdvAgency->payment_date = Carbon::now();

            $payDetails = array('paymentMode' => 'Cash', 'id' => $req->applicationId, 'amount' => $mAdvAgency->payment_amount, 'demand_amount' => $mAdvAgency->demand_amount, 'workflowId' => $mAdvAgency->workflow_id, 'userId' => $mAdvAgency->citizen_id, 'ulbId' => $mAdvAgency->ulb_id, 'transDate' => Carbon::now(), 'paymentId' => $pay_id);

            $mAdvAgency->payment_details = json_encode($payDetails);
            if ($mAdvAgency->renew_no == NULL) {
                $mAdvAgency->valid_from = Carbon::now();
                $mAdvAgency->valid_upto = Carbon::now()->addYears(5)->subDay(1);
            } else {
                $previousApplication = $this->findPreviousApplication($mAdvAgency->license_no);
                $mAdvAgency->valid_from = $previousApplication->valid_upto;
                $mAdvAgency->valid_upto = Carbon::createFromFormat('Y-m-d', $previousApplication->valid_upto)->addYears(5)->subDay(1);
            }
            $mAdvAgency->save();
            $renewal_id = $mAdvAgency->last_renewal_id;

            // Renewal Table Updation
            $mAdvAgencyRenewal = AdvAgencyRenewal::find($renewal_id);
            $mAdvAgencyRenewal->payment_status = 1;
            $mAdvAgencyRenewal->payment_amount =  $mAdvAgency->payment_amount;
            $mAdvAgencyRenewal->demand_amount =  $mAdvAgency->demand_amount;
            $mAdvAgencyRenewal->payment_id =  $pay_id;
            $mAdvAgencyRenewal->payment_date = Carbon::now();
            $mAdvAgencyRenewal->payment_mode = "Cash";
            $mAdvAgencyRenewal->valid_from = $mAdvAgency->valid_from;
            $mAdvAgencyRenewal->valid_upto = $mAdvAgency->valid_upto;
            $mAdvAgencyRenewal->payment_details = json_encode($payDetails);
            $ret['status'] = $mAdvAgencyRenewal->save();
            $ret['paymentId'] = $pay_id;
            return  $ret;
        }
    }


    // Find Previous Application Valid Date
    public function findPreviousApplication($license_no)
    {
        return $details = AdvAgencyRenewal::select('valid_upto')
            ->where('license_no', $license_no)
            ->orderByDesc('id')
            ->skip(1)->first();
    }


    /**
     * | Get Reciept Details 
     */
    public function getApprovalLetter($applicationId)
    {
        $recieptDetails = AdvAgency::select(
            'adv_agencies.approve_date',
            'adv_agencies.entity_name as applicant_name',
            'adv_agencies.application_no',
            'adv_agencies.license_no',
            'adv_agencies.payment_date as license_start_date',
            DB::raw('CONCAT(application_date,id) AS reciept_no')
        )
            ->where('adv_agencies.id', $applicationId)
            ->first();
        // $recieptDetails->payment_details=json_decode($recieptDetails->payment_details);
        return $recieptDetails;
    }

    /**
     * | Approve List For Report
     */
    public function approveListForReport()
    {
        return AdvAgency::select('id', 'application_no', 'entity_name', 'application_date', 'application_type', 'ulb_id', DB::raw("'Approve' as application_status"));
    }
}

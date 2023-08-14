<?php

namespace App\Models\Markets;

use App\Models\Advertisements\WfActiveDocument;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarDharamshala extends Model
{
    use HasFactory;

    /**
     * Summary of allApproveList
     * @return void
     */
    public function allApproveList()
    {
        return MarDharamshala::select(
            'mar_dharamshalas.id',
            'mar_dharamshalas.application_no',
            'mar_dharamshalas.application_date',
            'mar_dharamshalas.entity_address',
            'mar_dharamshalas.payment_amount',
            'mar_dharamshalas.entity_name',
            'mar_dharamshalas.applicant',
            'mar_dharamshalas.applicant as owner_name',
            'mar_dharamshalas.mobile as mobile_no',
            'mar_dharamshalas.approve_date',
            'mar_dharamshalas.payment_status',
            'mar_dharamshalas.citizen_id',
            'mar_dharamshalas.ulb_id',
            'mar_dharamshalas.valid_upto',
            'mar_dharamshalas.workflow_id',
            'mar_dharamshalas.license_no',
            'mar_dharamshalas.application_type',
            DB::raw("'dharamshala' as type"),
            'um.ulb_name as ulb_name',
        )
            ->join('ulb_masters as um', 'um.id', '=', 'mar_dharamshalas.ulb_id')
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
            $activeDharamashala = MarActiveDharamshala::where('application_no', $list['application_no'])->count();
            $current_date = carbon::now()->format('Y-m-d');
            $notify_date = carbon::parse($list['valid_upto'])->subDay(30)->format('Y-m-d');
            if ($current_date >= $notify_date) {
                if ($activeDharamashala == 0) {
                    $allApproveList[$key]['renew_option'] = '1';     // Renew option Show
                } else {
                    $allApproveList[$key]['renew_option'] = '0';     // Already Renew
                }
            }
            if ($current_date < $notify_date) {
                $allApproveList[$key]['renew_option'] = '0';      // Renew option Not Show 0
            }
            if ($list['valid_upto'] < $current_date) {
                $allApproveList[$key]['renew_option'] = 'Expired';    // Renew Expired 
            }
        }
        if ($userType == 'Citizen') {
            return collect($allApproveList)->where('citizen_id', $citizenId)->values();
        } else {
            return collect($allApproveList)->values();
        }
    }

    /**
     * | Get Application Details FOr Payments
     */
    public function getApplicationDetailsForPayment($id)
    {
        return MarDharamshala::where('id', $id)
            ->select(
                'id',
                'application_no',
                'application_date',
                'entity_name',
                'payment_amount',
                'approve_date',
                'ulb_id',
                'workflow_id',
            )
            ->first();
    }

    /**
     * | Get Payment vai Cash
     */
    public function paymentByCash($req)
    {

        if ($req->status == '1') {
            // Dharamshala Table Update
            $mMarDharamshala = MarDharamshala::find($req->applicationId);
            $mMarDharamshala->payment_status = $req->status;
            $mMarDharamshala->payment_mode = "Cash";
            $pay_id = $mMarDharamshala->payment_id = "Cash-$req->applicationId-" . time();
            // $mAdvCheckDtls->remarks = $req->remarks;
            $mMarDharamshala->payment_date = Carbon::now();

            $payDetails = array('paymentMode' => 'Cash', 'id' => $req->applicationId, 'amount' => $mMarDharamshala->payment_amount, 'demand_amount' => $mMarDharamshala->demand_amount, 'workflowId' => $mMarDharamshala->workflow_id, 'userId' => $mMarDharamshala->citizen_id, 'ulbId' => $mMarDharamshala->ulb_id, 'transDate' => Carbon::now(), 'paymentId' => $pay_id);

            $mMarDharamshala->payment_details = json_encode($payDetails);
            if ($mMarDharamshala->renew_no == NULL) {
                $mMarDharamshala->valid_from = Carbon::now();
                $mMarDharamshala->valid_upto = Carbon::now()->addYears(1)->subDay(1);
            } else {
                $previousApplication = $this->findPreviousApplication($mMarDharamshala->application_no);
                $mMarDharamshala->valid_from = $previousApplication->valid_upto;
                $mMarDharamshala->valid_upto = Carbon::createFromFormat('Y-m-d', $previousApplication->valid_upto)->addYears(1)->subDay(1);
            }
            $mMarDharamshala->save();
            $renewal_id = $mMarDharamshala->last_renewal_id;

            // Renewal Table Updation
            $mMarDharamshalaRenewal = MarDharamshalaRenewal::find($renewal_id);
            $mMarDharamshalaRenewal->payment_status = 1;
            $mMarDharamshalaRenewal->payment_mode = "Cash";
            $mMarDharamshalaRenewal->payment_id =  $pay_id;
            $mMarDharamshalaRenewal->payment_amount =  $mMarDharamshala->payment_amount;
            $mMarDharamshalaRenewal->demand_amount =  $mMarDharamshala->demand_amount;
            $mMarDharamshalaRenewal->payment_date = Carbon::now();
            $mMarDharamshalaRenewal->valid_from = $mMarDharamshala->valid_from;
            $mMarDharamshalaRenewal->valid_upto = $mMarDharamshala->valid_upto;
            $mMarDharamshalaRenewal->payment_details = json_encode($payDetails);
            $status = $mMarDharamshalaRenewal->save();
            $returnData['status'] = $status;
            $returnData['payment_id'] = $pay_id;
            return $returnData;
        }
    }


    // Find Previous Payment Date
    public function findPreviousApplication($application_no)
    {
        return $details = MarDharamshalaRenewal::select('valid_upto')
            ->where('application_no', $application_no)
            ->orderByDesc('id')
            ->skip(1)->first();
    }


    /**
     * | Get Application Details For Renew Applications
     */
    public function applicationDetailsForRenew($appId)
    {
        $details = MarDharamshala::select(
            'mar_dharamshalas.*',
            'mar_dharamshalas.organization_type as organization_type_id',
            'mar_dharamshalas.land_deed_type as land_deed_type_id',
            'mar_dharamshalas.water_supply_type as water_supply_type_id',
            'mar_dharamshalas.electricity_type as electricity_type_id',
            'mar_dharamshalas.security_type as security_type_id',
            'ly.string_parameter as license_year_name',
            'rw.ward_name as resident_ward_name',
            'ot.string_parameter as organization_type_name',
            'ldt.string_parameter as land_deed_type_name',
            'wt.string_parameter as water_supply_type_name',
            'et.string_parameter as electricity_type_name',
            'ew.ward_name as entity_ward_name',
            'st.string_parameter as security_type_name',
            'pw.ward_name as permanent_ward_name',
            'ulb.ulb_name',
        )
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('mar_dharamshalas.license_year::int'))
            ->leftJoin('ulb_ward_masters as rw', 'rw.id', '=', DB::raw('mar_dharamshalas.residential_ward_id::int'))
            ->leftJoin('ref_adv_paramstrings as ot', 'ot.id', '=', DB::raw('mar_dharamshalas.organization_type::int'))
            ->leftJoin('ref_adv_paramstrings as ldt', 'ldt.id', '=', DB::raw('mar_dharamshalas.land_deed_type::int'))
            ->leftJoin('ref_adv_paramstrings as wt', 'wt.id', '=', DB::raw('mar_dharamshalas.water_supply_type::int'))
            ->leftJoin('ref_adv_paramstrings as et', 'et.id', '=', DB::raw('mar_dharamshalas.electricity_type::int'))
            ->leftJoin('ref_adv_paramstrings as st', 'st.id', '=', DB::raw('mar_dharamshalas.security_type::int'))
            ->leftJoin('ulb_ward_masters as ew', 'ew.id', '=', 'mar_dharamshalas.entity_ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'mar_dharamshalas.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'mar_dharamshalas.ulb_id')
            ->where('mar_dharamshalas.id', $appId)->first();
        if (!empty($details)) {
            $mWfActiveDocument = new WfActiveDocument();
            $documents = $mWfActiveDocument->uploadDocumentsViewById($appId, $details->workflow_id);
            $details['documents'] = $documents;
        }
        return $details;
    }

    /**
     * | Get Payment Details After Payment
     */
    public function getPaymentDetails($paymentId)
    {
        $details = MarDharamshala::select(
            'mar_dharamshalas.payment_amount',
            'mar_dharamshalas.payment_id',
            'mar_dharamshalas.payment_date',
            'mar_dharamshalas.permanent_address as address',
            'mar_dharamshalas.applicant',
            'mar_dharamshalas.entity_name',
            'mar_dharamshalas.application_no',
            'mar_dharamshalas.license_no',
            'mar_dharamshalas.valid_from',
            'mar_dharamshalas.valid_upto',
            'mar_dharamshalas.application_date as applyDate',
            'mar_dharamshalas.holding_no',
            'mar_dharamshalas.trade_license_no',
            'mar_dharamshalas.rule',
            'mar_dharamshalas.payment_details',
            'mar_dharamshalas.payment_mode',
            'mar_dharamshalas.floor_area',
            'mar_dharamshalas.application_date as applyDate',
            'ulb_masters.ulb_name as ulbName',
            'ulb_masters.logo as ulbLogo',
            'ly.string_parameter as licenseYear',
            'wn.ward_name as wardNo',
            DB::raw("'Market' as module"),
        )
            ->leftjoin('ulb_masters', 'mar_dharamshalas.ulb_id', '=', 'ulb_masters.id')
            ->leftjoin('ulb_ward_masters as wn', 'mar_dharamshalas.entity_ward_id', '=', 'wn.id')
            ->leftjoin('ref_adv_paramstrings as ly', DB::raw('mar_dharamshalas.license_year::int'), '=', 'ly.id')
            ->where('mar_dharamshalas.payment_id', $paymentId)
            ->first();
        $details->payment_details = json_decode($details->payment_details);
        $details->towards = "Dharamshala";
        $details->payment_date = Carbon::createFromFormat('Y-m-d', $details->payment_date)->format('d-m-Y');
        $details->applyDate = Carbon::createFromFormat('Y-m-d', $details->applyDate)->format('d-m-Y');
        $details->valid_from = Carbon::createFromFormat('Y-m-d', $details->valid_from)->format('d-m-Y');
        $details->valid_upto = Carbon::createFromFormat('Y-m-d', $details->valid_upto)->format('d-m-Y');
        return $details;
    }

    /**
     * | Get Approve List For Report
     */
    public function approveListForReport()
    {
        return MarDharamshala::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'organization_type', 'ulb_id', 'license_year', DB::raw("'Approve' as application_status"));
    }

    /**
     * | Get Reciept Details 
     * | Created On : 23/6/2023
     */
    public function getApprovalLetter($applicationId)
    {
        $recieptDetails = MarDharamshala::select(
            'mar_dharamshalas.approve_date',
            'mar_dharamshalas.applicant as applicant_name',
            'mar_dharamshalas.application_no',
            'mar_dharamshalas.license_no',
            'mar_dharamshalas.payment_date as license_start_date',
            DB::raw('CONCAT(application_date,id) AS reciept_no')
        )
            ->where('mar_dharamshalas.id', $applicationId)
            ->first();
        return $recieptDetails;
    }
}

<?php

namespace App\Models\Advertisements;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvVehicle extends Model
{
    use HasFactory;

    /**
     * | Get all approve application list
     */
    public function allApproveList()
    {
        return AdvVehicle::select(
            'adv_vehicles.id',
            'application_no',
            DB::raw("TO_CHAR(adv_vehicles.application_date, 'DD-MM-YYYY') as application_date"),
            'adv_vehicles.application_type',
            'adv_vehicles.applicant',
            'adv_vehicles.applicant as owner_name',
            'adv_vehicles.entity_name',
            'adv_vehicles.mobile_no',
            'adv_vehicles.license_no',
            'adv_vehicles.payment_status',
            'adv_vehicles.payment_amount',
            'adv_vehicles.approve_date',
            'adv_vehicles.citizen_id',
            'adv_vehicles.valid_upto',
            'adv_vehicles.valid_from',
            'adv_vehicles.user_id',
            'adv_vehicles.ulb_id',
            'adv_vehicles.workflow_id',
            DB::raw("'movableVehicle' as type"),
            'um.ulb_name as ulb_name',
        )
            ->join('ulb_masters as um', 'um.id', '=', 'adv_vehicles.ulb_id')
            ->orderByDesc('adv_vehicles.id')
            ->get();
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listApproved($citizenId, $userType)
    {
        $allApproveList = $this->allApproveList();
        foreach ($allApproveList as $key => $list) {
            $activeVehicle = AdvActiveVehicle::where('application_no', $list['application_no'])->count();
            $current_date = carbon::now()->format('Y-m-d');
            $notify_date = carbon::parse($list['valid_upto'])->subDay(30)->format('Y-m-d');
            if ($current_date >= $notify_date) {
                if ($activeVehicle == 0) {
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
            return collect($allApproveList->where('citizen_id', $citizenId))->values();
        } else {
            return collect($allApproveList)->values();
        }
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listjskApprovedApplication($userId)
    {
        return AdvVehicle::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                'application_date',
                'applicant',
                'entity_name',
                'payment_status',
                'payment_amount',
                'approve_date',
            )
            ->orderByDesc('temp_id')
            ->get();
    }

    /**
     * | Get Application Details FOr Payments
     */
    public function detailsForPayments($id)
    {
        return AdvVehicle::where('id', $id)
            ->select(
                'id',
                'application_no',
                'application_date',
                'applicant',
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
        $details = AdvVehicle::select(
            'adv_vehicles.payment_amount',
            'adv_vehicles.payment_id',
            'adv_vehicles.payment_date',
            'adv_vehicles.permanent_address as address',
            'adv_vehicles.applicant',
            'adv_vehicles.payment_details',
            'adv_vehicles.payment_mode',
            'adv_vehicles.application_no',
            'adv_vehicles.license_no',
            'adv_vehicles.license_from as valid_from',
            'adv_vehicles.license_to as valid_upto',
            'adv_vehicles.vehicle_name',
            'adv_vehicles.vehicle_no',
            'adv_vehicles.trade_license_no',
            'adv_vehicles.application_date as applyDate',
            'ulb_masters.ulb_name as ulbName',
            'ulb_masters.logo as ulbLogo',
            'wn.ward_name as wardNo',
            DB::raw("'Advertisement' as module"),
        )
            ->leftjoin('ulb_masters', 'adv_vehicles.ulb_id', '=', 'ulb_masters.id')
            ->leftjoin('ulb_ward_masters as wn', DB::raw('adv_vehicles.ward_id::int'), '=', 'wn.id')
            ->where('adv_vehicles.payment_id', $paymentId)
            ->first();
        $details->payment_details = json_decode($details->payment_details);
        $details->towards = "Movable Vehicle";
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
            // Self Privateland Table Update
            $mAdvVehicle = AdvVehicle::find($req->applicationId);        // Application ID
            $mAdvVehicle->payment_status = $req->status;
            $mAdvVehicle->payment_mode = "Cash";
            $pay_id = $mAdvVehicle->payment_id = "Cash-$req->applicationId-" . time();
            // $mAdvCheckDtls->remarks = $req->remarks;
            $mAdvVehicle->payment_date = Carbon::now();

            // Payment Details
            $payDetails = array('paymentMode' => 'Cash', 'id' => $req->applicationId, 'amount' => $mAdvVehicle->payment_amount, 'demand_amount' => $mAdvVehicle->demand_amount, 'workflowId' => $mAdvVehicle->workflow_id, 'userId' => $mAdvVehicle->citizen_id, 'ulbId' => $mAdvVehicle->ulb_id, 'transDate' => Carbon::now(), 'paymentId' => $pay_id);

            $mAdvVehicle->payment_details =  json_encode($payDetails);
            if ($mAdvVehicle->renew_no == NULL) {
                $mAdvVehicle->valid_from = Carbon::now();
                $mAdvVehicle->valid_upto = Carbon::now()->addYears(1)->subDay(1);
            } else {
                $previousApplication = $this->findPreviousApplication($mAdvVehicle->license_no);
                $mAdvVehicle->valid_from = $previousApplication->valid_upto;
                $mAdvVehicle->valid_upto = Carbon::createFromFormat('Y-m-d', $previousApplication->valid_upto)->addYears(1)->subDay(1);
            }
            $mAdvVehicle->save();
            $renewal_id = $mAdvVehicle->last_renewal_id;

            // Privateland Renewal Table Updation
            $mAdvVehicleRenewal = AdvVehicleRenewal::find($renewal_id);
            $mAdvVehicleRenewal->payment_status = 1;
            $mAdvVehicleRenewal->payment_id =  $pay_id;
            $mAdvVehicleRenewal->payment_date = Carbon::now();
            $mAdvVehicleRenewal->payment_mode = "Cash";
            $mAdvVehicleRenewal->payment_amount = $mAdvVehicle->payment_amount;
            $mAdvVehicleRenewal->demand_amount = $mAdvVehicle->demand_amount;
            $mAdvVehicleRenewal->valid_from = $mAdvVehicle->valid_from;
            $mAdvVehicleRenewal->valid_upto = $mAdvVehicle->valid_upto;
            $mAdvVehicleRenewal->payment_details = json_encode($payDetails);;
            $status = $mAdvVehicleRenewal->save();
            $returnData['status'] = $status;
            $returnData['payment_id'] = $pay_id;
            return $returnData;
        }
    }

    // Find Previous Payment Date
    public function findPreviousApplication($license_no)
    {
        return $details = AdvVehicleRenewal::select('valid_upto')
            ->where('license_no', $license_no)
            ->orderByDesc('id')
            ->skip(1)->first();
    }

    /**
     * | Get Application Details for Renew application
     */
    public function applicationDetailsForRenew($appId)
    {
        $details = AdvVehicle::select(
            'adv_vehicles.*',
            'adv_vehicles.typology as typology_id',
            'adv_vehicles.display_type as display_type_id',
            'adv_vehicles.vehicle_type as vehicle_type_id',
            'dt.string_parameter as display_type',
            'vt.string_parameter as vehicle_type',
            'typo.descriptions as typology',
            'w.ward_name',
            'pw.ward_name as permanent_ward_name',
            'ulb.ulb_name',
        )
            ->leftJoin('ref_adv_paramstrings as dt', 'dt.id', '=', DB::raw('adv_vehicles.display_type::int'))
            ->leftJoin('ref_adv_paramstrings as vt', 'vt.id', '=', DB::raw('adv_vehicles.vehicle_type::int'))
            ->leftJoin('adv_typology_mstrs as typo', 'typo.id', '=', 'adv_vehicles.typology')
            ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'adv_vehicles.ward_id')
            ->leftJoin('ulb_ward_masters as pw', 'pw.id', '=', 'adv_vehicles.permanent_ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'adv_vehicles.ulb_id')
            ->where('adv_vehicles.id', $appId)->first();
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
        $recieptDetails = AdvVehicle::select(
            'adv_vehicles.approve_date',
            'adv_vehicles.applicant as applicant_name',
            'adv_vehicles.application_no',
            'adv_vehicles.license_no',
            'adv_vehicles.payment_date as license_start_date',
            DB::raw('CONCAT(application_date,id) AS reciept_no')
        )
            ->where('adv_vehicles.id', $applicationId)
            ->first();
        return $recieptDetails;
    }

    /**
     * | Approve List For Report 
     */
    public function approveListForReport()
    {
        return AdvVehicle::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'ulb_id', DB::raw("'Approve' as application_status"));
    }
}

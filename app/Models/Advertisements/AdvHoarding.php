<?php

namespace App\Models\Advertisements;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvHoarding extends Model
{
    use HasFactory;

    /**
     * | All Approve liST
     */
    public function allApproveList()
    {
        return AdvHoarding::select(
            'id',
            'application_no',
            'license_no',
            DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            'payment_status',
            'payment_amount',
            'approve_date',
            'citizen_id',
            'valid_upto',
            'property_owner_mobile_no as mobile_no',
            'property_owner_name as owner_name',
            'user_id',
            'ulb_id',
            'valid_upto',
            'valid_from',
            'is_archived',
            'is_blacklist',
            'workflow_id',
            DB::raw("'hoarding' as type"),
            DB::raw("'' as entity_name")
        )
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | All Approve liST
     */
    public function allApproveList1()
    {
        return AdvHoarding::select(
            'id',
            'application_no',
            'license_no',
            DB::raw("TO_CHAR(application_date, 'DD-MM-YYYY') as application_date"),
            'payment_status',
            'payment_amount',
            'approve_date',
            'citizen_id',
            'valid_upto',
            'property_owner_mobile_no as mobile_no',
            'property_owner_name as owner_name',
            'user_id',
            'ulb_id',
            'valid_upto',
            'valid_from',
            'is_archived',
            'is_blacklist',
            'workflow_id',
            DB::raw("'hoarding' as type"),
            DB::raw("'' as entity_name")
        )
            ->orderByDesc('id');
        // ->get();
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listApproved($citizenId, $usertype)
    { 
       $allApproveList = $this->allApproveList1();
        // foreach ($allApproveList as $key => $list) { 
        //     $current_date = Carbon::now()->format('Y-m-d');
        //     $notify_date = carbon::parse($list['valid_upto'])->subDay(30)->format('Y-m-d');
        //     if ($current_date >= $notify_date) {
        //         $allApproveList[$key]['renew_option'] = '1';     // Renew option Show
        //     }
        //     if ($current_date < $notify_date) {
        //         $allApproveList[$key]['renew_option'] = '0';      // Renew option Not Show
        //     }
        //     if ($list['valid_upto'] < $current_date) {
        //         $allApproveList[$key]['renew_option'] = 'Expired';    // Renew Expired
        //     }
        // }
        // echo $usertype; die;
        // return $allApproveList;
        if ($usertype == 'Citizen') {
            return $allApproveList->where('citizen_id', $citizenId)->where('payment_status', '1')->where('is_archived', false)->where('is_blacklist', false);
        } else {
            return $allApproveList->where('payment_status', '1')->where('is_archived', false)->where('is_blacklist', false);
        }
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function getRenewActiveApplications($citizenId, $usertype)
    {
        $allApproveList = $this->allApproveList1();
        if ($usertype == 'Citizen') {
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
            $list = $allApproveList->where('citizen_id', $citizenId)->where('payment_status', '1');
        } else {
            $list = $allApproveList->where('payment_status', '1');
        }
        return $list;
        $renewList = array();
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $val) {
                $currentDate = carbon::now()->format('Y-m-d');
                $notifyDate = carbon::parse($val['valid_upto'])->subDays(30)->format('Y-m-d');
                if ($notifyDate <= $currentDate) {
                    $renewList[] = $val;
                }
            }
            return $renewList;
        } else {
            return $list;
        }
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listUnpaid($citizenId, $usertype)
    {
        $allApproveList = $this->allApproveList1();
        if ($usertype == 'Citizen') {
            return $allApproveList->where('citizen_id', $citizenId)->where('payment_status', '0');
        } else {
            return $allApproveList->where('payment_status', 0);
        }
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listJskApprovedApplication($userId)
    {
        return AdvHoarding::where('user_id', $userId)
            ->select(
                'id',
                'application_no',
                'application_date',
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
        return AdvHoarding::where('id', $id)
            ->select(
                'id',
                'application_no',
                'application_date',
                'payment_status',
                'payment_amount',
                'approve_date',
                'property_type',
                'ulb_id',
                'workflow_id',
            )
            ->first();
    }

    /**
     * | Make Agency Dashboard
     */
    public function agencyDashboard($citizenId, $licenseYear)
    {
        //Approved Application
        $data['approvedAppl'] = AdvHoarding::select('*')
            ->where(['payment_status' => 1, 'citizen_id' => $citizenId, 'is_archived' => false, 'is_blacklist' => false,'license_year'=>$licenseYear])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('MY'); // grouping by months
            });
        $allApproved = collect();
        $data['countApprovedAppl'] = $data['approvedAppl']->map(function ($item, $key) use ($allApproved) {
            $allApproved->push($item->count());
            return $data[$key] = $item->count();
        });
        $data['countApprovedAppl']['totalApproved'] = $allApproved->sum();

        // Unpaid Application
        $data['unpaideAppl'] = AdvHoarding::select('*')
            ->where(['payment_status' => 0, 'citizen_id' => $citizenId,'license_year'=>$licenseYear])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('MY'); // grouping by months
            });
        $allUnpaid = collect();
        $data['countUnpaideAppl'] = $data['unpaideAppl']->map(function ($item, $key) use ($allUnpaid) {
            $allUnpaid->push($item->count());
            return $data[$key] = $item->count();
        });
        $data['countUnpaideAppl']['totalUnpaid'] = $allUnpaid->sum();


        //pending Application
        $data['pendindAppl'] = AdvActiveHoarding::select('*')
            ->where(['citizen_id' => $citizenId])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('MY'); // grouping by months
            });
        $allPending = collect();
        $data['countPendindAppl'] = $data['pendindAppl']->map(function ($item, $key) use ($allPending) {
            $allPending->push($item->count());
            return $data[$key] = $item->count();
        });
        $data['countPendindAppl']['totalPending'] = $allPending->sum();


        // Rejected Application
        $data['rejectAppl'] = AdvRejectedHoarding::select('*')
            ->where(['citizen_id' => $citizenId,'license_year'=>$licenseYear])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('MY'); // grouping by months
            });
        $allRejected = collect();
        $data['countRejectAppl'] = $data['rejectAppl']->map(function ($item, $key) use ($allRejected) {
            $allRejected->push($item->count());
            return $data[$key] = $item->count();
        });
        $data['countRejectAppl']['totalRejected'] = $allRejected->sum();

        // $data['ulb_id'] = AdvAgency::select('ulb_id')->where(['citizen_id' => $citizenId])->first()->ulb_id;
        // $data['ulbId']=;

        $data3['payment'] = AdvHoardingRenewal::select('payment_date', 'payment_amount', 'application_type')
            ->where(['citizen_id' => $citizenId,'license_year'=>$licenseYear])
            ->get();
        // ->groupBy(function ($val) {
        //     return Carbon::parse($val->payment_date)->format('M');
        // });

        $newPayment = collect();
        $renewPayment = collect();
        // return $data2;
        $data1['payments'] = $data3['payment']->map(function ($item, $key) use ($newPayment, $renewPayment) {
            if ($item['application_type'] == 'New Apply')
                $newPayment->push($item->payment_amount);
            else
                $renewPayment->push($item->payment_amount);
        });
        $currentFYear = DB::table('ref_adv_paramstrings')->select('string_parameter')->where('id', $licenseYear)->first()->string_parameter;
        $data['newPayment'] = $newPayment->sum();
        $data['renewPayment'] = $renewPayment->sum();
        $data['financialYear'] = $currentFYear;
        $data['totalPayment'] = $newPayment->sum() + $renewPayment->sum();
        return $data;
    }

    /**
     * | Make Agency Dashboard for Graph
     */
    public function agencyDashboardGraph($citizenId, $licenseYear)
    { // get from and throung date
        $from_date = Carbon::parse(date('Y-m-d', strtotime(date('Y-04-01'))));
        $through_date = Carbon::parse(date('Y-m-d'));

        // get total number of minutes between from and throung date
        $shift_difference = floor($from_date->diffInDays($through_date) / 30);

        // Approved Application
        $data['approvedAppl'] = AdvHoarding::select('*')
            ->where(['citizen_id' => $citizenId, 'license_year' => $licenseYear])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->application_date)->format('M'); // grouping by months
            });
        $allApproved = collect();
        $data1['countApprovedAppl'] = $data['approvedAppl']->map(function ($item, $key) use ($allApproved) {
            $allApproved->push($item->count());
            return $data[$key] = $item->count();
        });
        $data1['countApprovedAppl']['totalApproved'] = $allApproved->sum();


        //pending Application
        $data['pendindAppl'] = AdvActiveHoarding::select('*')
            ->where(['citizen_id' => $citizenId, 'license_year' => $licenseYear])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->application_date)->format('M'); // grouping by months
            });
        $allPending = collect();
        $data1['countPendindAppl'] = $data['pendindAppl']->map(function ($item, $key) use ($allPending) {
            $allPending->push($item->count());
            return $data[$key] = $item->count();
        });
        $data1['countPendindAppl']['totalPending'] = $allPending->sum();

        // Rejected Application
        $data['rejectAppl'] = AdvRejectedHoarding::select('*')
            ->where(['citizen_id' => $citizenId])
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->application_date)->format('MY'); // grouping by months
            });
        $allRejected = collect();
        $data1['countRejectAppl'] = $data['rejectAppl']->map(function ($item, $key) use ($allRejected) {
            $allRejected->push($item->count());
            return $data[$key] = $item->count();
        });
        $data1['countRejectAppl']['totalRejected'] = $allRejected->sum();

        $data2['payment'] = AdvHoardingRenewal::select('payment_date', 'payment_amount')
            ->where(['citizen_id' => $citizenId])
            ->get()
            ->groupBy(function ($val) {
                return Carbon::parse($val->payment_date)->format('M');
            });

        $payment = array();
        $multiplied = $data2['payment']->map(function ($item, $key) use ($payment) {
            return $payment[$key] = $item->sum('payment_amount');
        });

        $finalData = array();
        for ($i = 0; $i < 12; $i++) {
            $m = $i -  $shift_difference;
            $x = strtotime("$m month");
            $finalData[$i]['month'] = date('M', $x);
            if (isset($data1['countPendindAppl'][date('M', $x)])) {
                $finalData[$i]['pending'] = $data1['countPendindAppl'][date('M', $x)];
            } else {
                $finalData[$i]['pending'] = 0;
            }
            if (isset($data1['countRejectAppl'][date('M', $x)])) {
                $finalData[$i]['reject'] = $data1['countRejectAppl'][date('M', $x)];
            } else {
                $finalData[$i]['reject'] = 0;
            }
            if (isset($data1['approvedAppl'][date('M', $x)])) {
                $finalData[$i]['approved'] = $data1['approvedAppl'][date('M', $x)];
            } else {
                $finalData[$i]['approved'] = 0;
            }
            $finalData[$i]['total'] = $finalData[$i]['approved'] + $finalData[$i]['reject'] + $finalData[$i]['pending'];
        }
        $paymentData = array();
        for ($i = 0; $i < 12; $i++) {
            $m = $i -  $shift_difference;
            $x = strtotime("$m month");
            $paymentData[$i]['month'] = date('M', $x);
            if (isset($multiplied[date('M', $x)])) {
                $paymentData[$i]['total'] = $multiplied[date('M', $x)];
            } else {
                $paymentData[$i]['total'] = 0;
            }
        }
        $finalData1['graph'] = $finalData;
        $finalData1['payment'] = $paymentData;
        return  $finalData1;
    }



    /**
     * | Get Payment Details
     */
    public function getPaymentDetails($paymentId)
    {
        $details = AdvHoarding::select(
            'adv_hoardings.payment_amount',
            'adv_hoardings.payment_id',
            'adv_hoardings.payment_date',
            // 'adv_hoardings.entity_address as address',
            DB::raw("case when adv_hoardings.zone_id = 1 then 'Zone A'
                              when adv_hoardings.zone_id = 2 then 'Zone B'
                              when adv_hoardings.zone_id = 3 then 'Zone C'
                        else 'N/A' end as address"),
            'adv_hoardings.property_owner_name as entity_name',
            'adv_hoardings.payment_details',
            'adv_hoardings.payment_mode',
            'adv_hoardings.valid_from',
            'adv_hoardings.valid_upto',
            'adv_hoardings.application_no',
            'adv_hoardings.license_no',
            'adv_hoardings.display_area',
            'adv_hoardings.application_date as applyDate',
            'ulb_masters.ulb_name as ulbName',
            'ulb_masters.logo as ulbLogo',
            'ly.string_parameter as licenseYear',
            DB::raw("'Advertisement' as module"),
        )
            ->leftjoin('ulb_masters', 'adv_hoardings.ulb_id', '=', 'ulb_masters.id')
            ->leftjoin('ref_adv_paramstrings as ly', 'adv_hoardings.license_year', '=', 'ly.id')
            ->where('adv_hoardings.payment_id', $paymentId)
            ->first();
        $details->payment_details = json_decode($details->payment_details);
        $details->towards = "Hoarding";
        $details->payment_date = Carbon::createFromFormat('Y-m-d', $details->payment_date)->format('d-m-Y');
        $details->valid_from = Carbon::createFromFormat('Y-m-d',  $details->valid_from)->format('d-m-Y');
        $details->valid_upto = Carbon::createFromFormat('Y-m-d',  $details->valid_upto)->format('d-m-Y');
        $details->applyDate = Carbon::createFromFormat('Y-m-d',  $details->applyDate)->format('d-m-Y');
        return $details;
    }

    /**
     * | Payment Via Cash
     */
    public function paymentByCash($req)
    {

        if ($req->status == '1') {
            // Self Agency License Table Update
            $mAdvHoarding = AdvHoarding::find($req->applicationId);        // Application ID
            $mAdvHoarding->payment_status = $req->status;
            $mAdvHoarding->payment_mode = "Cash";
            $pay_id = $mAdvHoarding->payment_id = "Cash-$req->applicationId-" . time();
            // $mAdvCheckDtls->remarks = $req->remarks;
            $mAdvHoarding->payment_date = Carbon::now();

            $payDetails = array('paymentMode' => 'Cash', 'id' => $req->applicationId, 'amount' => $mAdvHoarding->payment_amount, 'demand_amount' => $mAdvHoarding->demand_amount, 'workflowId' => $mAdvHoarding->workflow_id, 'userId' => $mAdvHoarding->citizen_id, 'ulbId' => $mAdvHoarding->ulb_id, 'transDate' => Carbon::now(), 'paymentId' => $pay_id);

            $mAdvHoarding->payment_details = json_encode($payDetails);
            if ($mAdvHoarding->renew_no == NULL) {
                $mAdvHoarding->valid_from = Carbon::now();
                $mAdvHoarding->valid_upto = Carbon::now()->addYears(1)->subDay(1);
            } else {
                $previousApplication = $this->findPreviousApplication($mAdvHoarding->application_no);
                $mAdvHoarding->valid_from =  $previousApplication->valid_upto;
                $mAdvHoarding->valid_upto = Carbon::createFromFormat('Y-m-d', $previousApplication->valid_upto)->addYears(1)->subDay(1);
            }
            $mAdvHoarding->save();
            $renewal_id = $mAdvHoarding->last_renewal_id;

            // Agency License Renewal Table Updation
            $mAdvHoardingRenewal = AdvHoardingRenewal::find($renewal_id);
            $mAdvHoardingRenewal->payment_status = 1;
            $mAdvHoardingRenewal->payment_mode = "Cash";
            $mAdvHoardingRenewal->payment_id =  $pay_id;
            $mAdvHoardingRenewal->payment_date = Carbon::now();
            $mAdvHoardingRenewal->payment_amount = $mAdvHoarding->payment_amount;
            $mAdvHoardingRenewal->demand_amount = $mAdvHoarding->demand_amount;
            $mAdvHoardingRenewal->valid_from = $mAdvHoarding->valid_from;
            $mAdvHoardingRenewal->valid_upto =  $mAdvHoarding->valid_upto;
            $mAdvHoardingRenewal->payment_details = json_encode($payDetails);;
            $status = $mAdvHoardingRenewal->save();
            $returnData['status'] = $status;
            $returnData['payment_id'] = $pay_id;
            return $returnData;
        }
    }


    // Find Previous Payment Date
    public function findPreviousApplication($application_no)
    {
        return $details = AdvHoarding::select('valid_upto')
            ->where('application_no', $application_no)
            ->orderByDesc('id')
            ->skip(1)->first();
    }

    /**
     * | Get Application Details For Renew
     */
    public function applicationDetailsForRenew($appId)
    {
        $details = AdvHoarding::select(
            'adv_hoardings.license_year',
            'adv_hoardings.display_location as location',
            'adv_hoardings.workflow_id',
            'adv_hoardings.longitude',
            'adv_hoardings.latitude',
            'adv_hoardings.length',
            'adv_hoardings.width',
            'adv_hoardings.display_area as area',
            'adv_hoardings.display_land_mark as landmark',
            'adv_hoardings.indicate_facing as facing',
            'adv_hoardings.property_type',
            'adv_hoardings.property_owner_name',
            'adv_hoardings.property_owner_address',
            'adv_hoardings.property_owner_city',
            'adv_hoardings.property_owner_mobile_no',
            'adv_hoardings.property_owner_whatsapp_no',
            'adv_hoardings.typology as typology_id',
            'adv_hoardings.license_year as license_year_id',
            'adv_hoardings.illumination',
            'adv_hoardings.material',
            'adv_hoardings.ward_id',
            'adv_hoardings.zone_id',
            'ly.string_parameter as license_year',
            // 'ill.string_parameter as illumination_name',
            'typo.descriptions as typology_name',
            'w.ward_name',
            'ulb.ulb_name',
        )
            ->leftJoin('adv_typology_mstrs as typo', 'typo.id', '=', DB::raw('adv_hoardings.typology::int'))
            ->leftJoin('ref_adv_paramstrings as ly', 'ly.id', '=', DB::raw('adv_hoardings.license_year::int'))
            // ->leftJoin('ref_adv_paramstrings as ill','ill.id','=',DB::raw('adv_hoardings.illumination::int'))
            ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'adv_hoardings.ward_id')
            ->leftJoin('ulb_masters as ulb', 'ulb.id', '=', 'adv_hoardings.ulb_id')
            ->where('adv_hoardings.id', $appId)->first();
        if (!empty($details)) {
            $mWfActiveDocument = new WfActiveDocument();
            $documents = $mWfActiveDocument->uploadDocumentsViewById($appId, $details->workflow_id);
            $details['documents'] = $documents;
        }
        return $details;
    }

    /**
     * | Get Application Approve List by Role Ids
     */
    public function listExpiredHording($citizenId, $usertype)
    {
        $allApproveList = $this->allApproveList1();
        $current_date = carbon::now()->format('Y-m-d');
        if ($usertype == 'Citizen') {
            return $allApproveList->where('citizen_id', $citizenId)->where('payment_status', '1')->where('valid_upto', '<', $current_date);
        } else {
            return $allApproveList->where('payment_status', '1');
        }
    }


    /**
     * | Get Application Archieved List by Role Ids
     */
    public function listHordingArchived($citizenId, $usertype)
    {
        $allApproveList = $this->allApproveList1();

        if ($usertype == 'Citizen') {
            return $allApproveList->where('citizen_id', $citizenId)->where('payment_status', '1')->where('is_archived', true);
        } else {
            return $allApproveList->where('payment_status', '1')->where('is_archived', true);
        }
    }


    /**
     * | Get Application Blacklist List by Role Ids
     */
    public function listHordingBlacklist($citizenId, $usertype)
    {
        $allApproveList = $this->allApproveList();

        if ($usertype == 'Citizen') {
            return collect($allApproveList)->where('citizen_id', $citizenId)->where('payment_status', '1')->where('is_blacklist', true)->values();
        } else {
            return collect($allApproveList)->where('payment_status', '1')->where('is_blacklist', true)->values();
        }
    }


    /**
     * | Get Reciept Details 
     */
    public function getApprovalLetter($applicationId)
    {
        $recieptDetails = AdvHoarding::select(
            'adv_hoardings.approve_date',
            'adv_hoardings.applicant as applicant_name',
            'adv_hoardings.application_no',
            'adv_hoardings.license_no',
            'adv_hoardings.payment_date as license_start_date',
            DB::raw('CONCAT(application_date,id) AS reciept_no')
        )
            ->where('adv_hoardings.id', $applicationId)
            ->first();
        // $recieptDetails->payment_details=json_decode($recieptDetails->payment_details);
        return $recieptDetails;
    }

    /**
     * | Approve List For Report
     */
    public function approveListForReport()
    {
        return AdvHoarding::select('id', 'application_no', 'application_date', 'application_type', 'license_year', 'ulb_id', DB::raw("'Approve' as application_status"));
    }
}

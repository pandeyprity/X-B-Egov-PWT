<?php

namespace App\Repository\Property\Concrete;

use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\EloquentModels\Common\ModelWard;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsDoc;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropFloor;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropProperty;
use App\Models\Property\PropTransaction;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ExpireLicence;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IPropertyBifurcation;
use App\Traits\Auth;
use App\Traits\Helper;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use App\Traits\Property\SafDoc;
use App\Traits\Property\WardPermission;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PropertyBifurcation implements IPropertyBifurcation
{

    use Auth;                                                               // Trait Used added by sandeep bara date 17-08-2022
    use WardPermission;
    use Workflow;
    use SAF;
    use Razorpay;
    use Helper;
    use SafDoc;

    protected $_common;
    protected $_modelWard;
    protected $_Saf;
    protected $_property;
    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
        $this->_Saf = new SafRepository();
        $this->_property = new PropertyDeactivate();
    }
    public function addRecord(Request $request)
    {
        try {
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mProperty  = $this->_property->getPropertyById($request->id);
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $mNowDateYm   = Carbon::now()->format("Y-m");
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $mUserType  = $this->_common->userType($refWorkflowId);
            $init_finish = $this->_common->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            if (!$init_finish) {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            } elseif (!$init_finish["initiator"]) {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }
            if (!$mProperty) {
                throw new Exception("Property Not Found");
            }

            $priv_data = PropActiveSaf::select("*")
                ->where("previous_holding_id", $mProperty->id)
                ->orderBy("id", "desc")
                ->first();
            if ($priv_data) {
                throw new Exception("Assesment already applied");
            }
            $mOwrners  = $this->_property->getPropOwnerByProId($mProperty->id);
            $mFloors    = $this->getFlooreDtl($mProperty->id);
            if ($request->getMethod() == "GET") {
                $data = [
                    "property" => $mProperty,
                    "owners"    => $mOwrners,
                    "floors"   => $mFloors,
                ];
                return responseMsg(true, '', remove_null($data));
            } elseif ($request->getMethod() == "POST") {
                // return($request->all());
                $assessmentTypeId   = $request->assessmentType;
                $previousHoldingId  = $request->oldHoldingId;
                $holdingNo          = $request->oldHoldingNo;
                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                    ->where('ulb_id', $refUlbId)
                    ->first();
                DB::beginTransaction();
                $safNo = [];
                $parentSaf = "";
                if (sizeOf($request->container) <= 1) {
                    throw new Exception("Atleast 2 Saf Record is required");
                }
                foreach ($request->container as $key => $val) {
                    $myRequest = new \Illuminate\Http\Request();
                    $myRequest->setMethod('POST');
                    $myRequest->request->add(['assessmentType' => $assessmentTypeId]);
                    $myRequest->request->add(['previousHoldingId' => $previousHoldingId]);
                    $myRequest->request->add(['holdingNo' => $holdingNo]);
                    foreach ($val as $key2 => $val2) {
                        $myRequest->request->add([$key2 => $val2]);
                    }
                    $safNo[$key] = $this->insertData($myRequest);
                    if ($myRequest->isAcquired) {
                        $parentSaf = $safNo[$key];
                    }
                }
                $safNo = $parentSaf;
                // DB::commit();
                DB::rollBack();
                return responseMsg(true, "Application Submitted Successfully. Your New SAF No is $safNo", ["safNo" => $safNo]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function inbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $refWorkflowMstrId     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if (!$refWorkflowMstrId) {
                throw new Exception("Workflow Not Available");
            }

            $mUserType = $this->_common->userType($refWorkflowId);
            $mWardPermission = $this->_common->WardPermission($refUserId);
            $mRole = $this->_common->getUserRoll($refUserId, $refUlbId, $refWorkflowMstrId->wf_master_id);
            $mJoins = "";
            if (!$mRole) {
                throw new Exception("You Are Not Authorized For This Action");
            }
            if ($mRole->is_initiator) {
                $mWardPermission = $this->_modelWard->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
                $mJoins = "leftjoin";
            } else {
                $mJoins = "join";
            }

            $mWardIds = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);

            $mRoleId = $mRole->role_id;
            $inputs = $request->all();
            // DB::enableQueryLog();          
            $application = PropActiveSaf::select(
                'prop_active_safs.saf_no',
                'prop_active_safs.id',
                'prop_active_safs.ward_mstr_id',
                'prop_active_safs.prop_type_mstr_id',
                'prop_active_safs.appartment_name',
                'ref_prop_types.property_type',
                'prop_active_safs.assessment_type',
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                DB::raw(
                    "prop_level_pendings.id AS level_id, 
                     ward.ward_name as ward_no"
                )
            )
                ->join("ref_prop_types", "ref_prop_types.id", "prop_active_safs.prop_type_mstr_id")
                ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
                // ->join('prop_ref_assessment_types as at', 'at.id', '=', 'prop_active_safs.assessment_type')
                ->$mJoins("prop_level_pendings", function ($join) use ($mRoleId) {
                    $join->on("prop_level_pendings.saf_id", "prop_active_safs.id")
                        ->where("prop_level_pendings.receiver_role_id", $mRoleId)
                        ->where("prop_level_pendings.status", 1)
                        ->where("prop_level_pendings.verification_status", 0);
                })
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email,',') AS email_id,
                                            saf_id
                                        FROM prop_active_safs_owners 
                                        WHERE status =1
                                        GROUP BY saf_id
                                        )owner"), function ($join) {
                    $join->on("owner.saf_id", "prop_active_safs.id");
                })
                ->where("prop_active_safs.status", 1)
                ->where("prop_active_safs.ulb_id", $refUlbId);
            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $application = $application->where(function ($query) use ($key) {
                    $query->orwhere('prop_active_safs.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('prop_active_safs.saf_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("prop_active_safs.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $mWardIds = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $application = $application
                    ->whereBetween('prop_level_pendings.created_at::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            if ($mRole->is_initiator) {
                $application = $application->whereIn('prop_active_safs.saf_pending_status', [0, 2]);
            } else {
                $application = $application->whereIn('prop_active_safs.saf_pending_status', [3]);
            }
            $application = $application
                ->where("prop_active_safs.workflow_id", $refWorkflowMstrId->id)
                ->where("prop_active_safs.is_aquired", true)
                ->whereIn('prop_active_safs.ward_mstr_id', $mWardIds)
                ->get();
            // dd(DB::getQueryLog());
            $data = [
                "userType"      => $mUserType,
                "wardList"      =>  $mWardPermission,
                "applications"  =>  $application,
            ];
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function outbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) {
                throw new Exception("Workflow Not Available");
            }
            $mUserType = $this->_common->userType($refWorkflowId);
            $ward_permission = $this->_common->WardPermission($user_id);
            $role = $this->_common->getUserRoll($user_id, $ulb_id, $workflowId->wf_master_id);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            if ($role->is_initiator || in_array(strtoupper($mUserType), ["JSK", "SUPER ADMIN", "ADMIN", "TL", "PMU", "PM"])) {
                $joins = "leftjoin";
                $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = objToArray($ward_permission);
            } else {
                $joins = "join";
            }
            $role_id = $role->role_id;

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);
            $inputs = $request->all();
            // DB::enableQueryLog();
            $application = PropActiveSaf::select(
                'prop_active_safs.saf_no',
                'prop_active_safs.id',
                'prop_active_safs.ward_mstr_id',
                'prop_active_safs.prop_type_mstr_id',
                'prop_active_safs.appartment_name',
                'ref_prop_types.property_type',
                'prop_active_safs.assessment_type',
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                DB::raw(
                    "ward.ward_name as ward_no"
                )
            )
                ->join("ref_prop_types", "ref_prop_types.id", "prop_active_safs.prop_type_mstr_id")
                ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
                // ->join('prop_ref_assessment_types as at', 'at.id', '=', 'prop_active_safs.assessment_type')
                ->$joins("prop_level_pendings", function ($join) use ($role_id) {
                    $join->on("prop_level_pendings.saf_id", "prop_active_safs.id")
                        ->where("prop_level_pendings.sender_role_id", $role_id)
                        ->where("prop_level_pendings.status", 1)
                        ->where("prop_level_pendings.verification_status", 0);
                })
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email,',') AS email_id,
                                            saf_id
                                        FROM prop_active_safs_owners 
                                        WHERE status =1
                                        GROUP BY saf_id
                                        )owner"), function ($join) {
                    $join->on("owner.saf_id", "prop_active_safs.id");
                })
                ->where("prop_active_safs.status", 1)
                ->where("prop_active_safs.ulb_id", $ulb_id);

            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $application = $application->where(function ($query) use ($key) {
                    $query->orwhere('prop_active_safs.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('prop_active_safs.saf_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("prop_active_safs.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $ward_ids = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $application = $application
                    ->whereBetween('prop_level_pendings.created_at::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            if (!$role->is_initiator) {
                $application = $application->whereIn('prop_active_safs.saf_pending_status', [2, 3]);
            } else {
                $application = $application->whereIn('prop_active_safs.saf_pending_status', [3]);
            }
            $application = $application
                ->where("prop_active_safs.workflow_id", $workflowId->id)
                ->where("prop_active_safs.is_aquired", true)
                ->whereIn('prop_active_safs.ward_mstr_id', $ward_ids)
                ->get();
            // dd(DB::getQueryLog());
            $data = [
                "userType"      => $mUserType,
                "wardList" => $ward_permission,
                "application" => $application,
            ];
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function postNextLevel(Request $request)
    {
        try {
            $receiver_user_type_id = "";
            $sms = "";
            $licence_pending = 3;
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\-, \s]+$/';
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->_common->getUserRoll($user_id, $ulb_id, $workflowId->wf_master_id);
            $init_finish = $this->_common->iniatorFinisher($user_id, $ulb_id, $refWorkflowId);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            $role_id = $role->role_id;
            $apply_from = $this->_common->userType($refWorkflowId);
            $rules = [
                "btn" => "required|in:btc,forward,backward",
                "safId" => "required|digits_between:1,9223372036854775807",
                "comment" => "required|min:10|regex:$regex",
            ];
            $message = [
                "btn.in" => "Button Value must be In BTC,FORWARD,BACKWARD",
                "comment.required" => "Comment Is Required",
                "comment.min" => "Comment Length can't be less than 10 charecters",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            if ($role->is_initiator && in_array($request->btn, ['btc', 'backward'])) {
                throw new Exception("Initator Can Not send Back The Application");
            }
            $saf_data = PropActiveSaf::find($request->safId);
            $level_data = $this->getLevelData($request->safId);

            if (!$saf_data) {
                throw new Exception("Data Not Found");
            } elseif ($saf_data->saf_pending_status == 1) {
                throw new Exception("Saf Is Already Approved");
            } elseif (!$role->is_initiator && isset($level_data->receiver_role_id) && $level_data->receiver_role_id != $role->role_id) {
                throw new Exception("You are not authorised for this action");
            } elseif (!$role->is_initiator && !$level_data) {
                throw new Exception("Data Not Found On Level. Please Contact Admin!!!...");
            } elseif (isset($level_data->receiver_role_id) && $level_data->receiver_role_id != $role->role_id) {
                throw new Exception("You Have Already Taken The Action On This Application");
            }
            if (!$init_finish) {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            } elseif (!$init_finish["initiator"]) {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            } elseif (!$init_finish["finisher"]) {
                throw new Exception("Finisher Not Available. Please Contact Admin !!!...");
            }

            // dd($role);
            if ($request->btn == "forward" && !$role->is_finisher && !$role->is_initiator) {
                $sms = "Application Forwarded To " . $role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;
            } elseif ($request->btn == "backward" && !$role->is_initiator) {
                $sms = "Application Forwarded To " . $role->backword_name;
                $receiver_user_type_id = $role->backward_role_id;
                $licence_pending = $init_finish["initiator"]['id'] == $role->backward_role_id ? 3 : $licence_pending;
            } elseif ($request->btn == "btc" && !$role->is_initiator) {
                $licence_pending = 2;
                $sms = "Application Forwarded To " . $init_finish["initiator"]['role_name'];
                $receiver_user_type_id = $init_finish["initiator"]['id'];
            } elseif ($request->btn == "forward" && !$role->is_initiator && $level_data) {
                $sms = "Application Forwarded ";
                $receiver_user_type_id = $level_data->sender_role_id;
            } elseif ($request->btn == "forward" && $role->is_initiator && !$level_data) {
                $licence_pending = 3;
                $sms = "Application Forwarded To " . $role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;
            } elseif ($request->btn == "forward" && $role->is_initiator && $level_data) {
                $licence_pending = 3;
                $sms = "Application Forwarded To ";
                $receiver_user_type_id = $level_data->sender_role_id;
            }
            if ($request->btn == "forward" && $role->is_initiator) {
                $doc = (array) null;
                $safs_temp = $this->getAllReletedSaf($saf_data->previous_holding_id);
                if (sizeOf($safs_temp) < 1) {
                    throw new Exception("Opps some errors occur!....");
                }
                foreach ($safs_temp as $key => $val) {
                    $documentsList = [];
                    $owneres = $this->getOwnereDtlBySId($val->id);
                    $documentsList = $this->getDocumentTypeList($val);
                    foreach ($owneres as $key2 => $val2) {
                        $data["documentsList"]["gender_document"] = $this->getDocumentList("gender_document");
                        $data["documentsList"]["dob_document"] = $this->getDocumentList("dob_document");
                        if ($val2->is_armed_force) {
                            $data["documentsList"]["armed_force_document"] = $this->getDocumentList("armed_force_document");
                        }
                        if ($val2->is_specially_abled) {
                            $data["documentsList"]["handicaped_document"] = $this->getDocumentList("handicaped_document");
                        }
                    }

                    if ($saf_data->payment_status != 1) {
                        throw new Exception("Payment is Not Clear");
                    }
                    foreach ($documentsList as $val) {
                        $data["documentsList"][$val->doc_type] = $this->getDocumentList($val->doc_type);
                        $data["documentsList"][$val->doc_type]["is_mandatory"] = 1;
                        if (in_array($val->doc_type, ["additional_doc", "no_elect_connection", "other"])) {
                            $data["documentsList"][$val->doc_type]["is_mandatory"] = 0;
                        }
                    }
                    foreach ($data["documentsList"] as $key3 => $val3) {
                        if (in_array($key3, ["Identity Proof", "gender_document", "dob_document", "armed_force_document", "handicaped_document"])) {
                            continue;
                        }
                        $data["documentsList"][$key3]["doc"] = $this->check_doc_exist($saf_data->id, $key3);
                        if (!isset($data["documentsList"][$key3]["doc"]["document_path"]) && $data["documentsList"][$key3]["is_mandatory"]) {
                            $doc[] = $key3 . " Not Uploaded";
                        }
                    }
                    foreach ($owneres as $key2 => $val2) {
                        // $owneres[$key2]["Identity Proof"] = $this->check_doc_exist_owner($saf_data->id, $val2->id);
                        // if (!isset($owneres[$key2]["Identity Proof"]["doc_path"])) 
                        // {
                        //     $doc[] = "Identity Proof Of " . $val2->owner_name . " Not Uploaded";
                        // }
                        $owneres[$key2]["gender_document"]  = $this->check_doc_exist_owner($saf_data->id, $val2->id, "gender_document");
                        if (!isset($owneres[$key2]["gender_document"]["doc_path"])) {
                            $doc[] = $val2->owner_name . " Gender Document Not Uploaded";
                        }
                        $owneres[$key2]["dob_document"]     = $this->check_doc_exist_owner($saf_data->id, $val2->id, "dob_document");;
                        if (!isset($owneres[$key2]["dob_document"]["doc_path"])) {
                            $doc[] = $val2->owner_name . " DOB Document Not Uploaded";
                        }
                        if ($val2->is_armed_force) {
                            $owneres[$key2]["armed_force_document"] = $this->check_doc_exist_owner($saf_data->id, $val2->id, "armed_force_document");
                            if (!isset($owneres[$key2]["armed_force_document"]["doc_path"])) {
                                $doc[] = $val2->owner_name . " Armed Force Document Not Uploaded";
                            }
                        }
                        if ($val2->is_specially_abled) {
                            $data["documentsList"]["handicaped_document"] = $this->getDocumentList("handicaped_document");
                            $owneres[$key2]["handicaped_document"] = $this->check_doc_exist_owner($saf_data->id, $val2->id, "handicaped_document");
                            if (!isset($owneres[$key2]["handicaped_document"]["doc_path"])) {
                                $doc[] = $val2->owner_name . " Handicaped Document Not Uploaded";
                            }
                        }
                    }
                }
                // if($doc)
                // {   $err = "";
                //     foreach($doc as $val)
                //     {
                //         $err.="<li>$val</li>";
                //     }                
                //     throw new Exception($err);
                // }
            }
            if ($request->btn == "forward" && in_array(strtoupper($apply_from), ["DA"])) {
                $safs_temp = $this->getAllReletedSaf($saf_data->previous_holding_id);
                $docs = [];
                if (sizeOf($safs_temp) < 1) {
                    throw new Exception("Opps some errors occur!....");
                }
                foreach ($safs_temp as $key => $val) {
                    array_push($docs, $this->getSafDocuments($val->id));
                }
                if (!$docs) {
                    throw new Exception("No Anny Document Found");
                }

                $docs = objToArray(collect($docs));
                $test = array_filter($docs, function ($val) {
                    foreach ($val as $keys => $vals) {
                        if ($vals["verify_status"] != 1) {
                            return True;
                        }
                    }
                });
                // if($test)
                // {
                //     throw new Exception("All Document Are Not Verified");
                // }


            }

            if (!$role->is_finisher && !$receiver_user_type_id) {
                throw new Exception("Next Role Not Found !!!....");
            }
            $data = "";
            DB::beginTransaction();
            if ($level_data) {

                $level_data->verification_status = 1;
                $level_data->receiver_user_id = $user_id;
                $level_data->remarks = $request->comment;
                $level_data->forward_date = Carbon::now()->format('Y-m-d');
                $level_data->forward_time = Carbon::now()->format('H:s:i');
                $level_data->save();
            }
            if (!$role->is_finisher || in_array($request->btn, ["backward", "btc"])) {
                $level_insert = new PropLevelPending;
                $level_insert->saf_id = $saf_data->id;
                $level_insert->sender_role_id = $role_id;
                $level_insert->receiver_role_id = $receiver_user_type_id;
                $level_insert->sender_user_id = $user_id;
                $level_insert->save();
                $saf_data->current_role = $receiver_user_type_id;
            }
            if ($role->is_finisher && $request->btn == "forward") {
                $licence_pending = 1;
                $sms = "Application Approved By " . $role->forword_name;
                $safs_temp = $this->getAllReletedSaf($saf_data->previous_holding_id);
                $holding = [];
                foreach ($safs_temp as $val) {
                    $myRequest = new \Illuminate\Http\Request();
                    $myRequest->setMethod('POST');
                    $myRequest->request->add(['workflowId' => $workflowId->id]);
                    $myRequest->request->add(['roleId' => 10]);
                    $myRequest->request->add(['safId'   => $val->id]);
                    $myRequest->request->add(['status'   => 1]);
                    $temholding = $this->_Saf->approvalRejectionSaf($myRequest);
                    $holding[$val->saf_no] = ($temholding->original['message']);
                    if ($val->is_aquired) {
                        $sms = $temholding->original['message'];
                    }
                }
                $data = $holding;
                $property = PropProperty::find($saf_data->previous_holding_id)->where("status", 1);
                if (!$property) {
                    throw new Exception("Somthig went worng!........");
                }
                $property->status = 0;
                $property->update();
            }

            if ($request->btn == "forward" && $role->is_initiator) {
                foreach ($safs_temp as $val) {
                    $saf = PropActiveSaf::find($val->id);
                    $val->doc_upload_status = 1;
                }
            }
            if ($request->btn == "forward" && in_array(strtoupper($apply_from), ["DA"])) {
                $nowdate = Carbon::now()->format('Y-m-d');
                foreach ($safs_temp as $val) {
                    $saf = PropActiveSaf::find($val->id);
                    $saf->doc_verify_status = 1;
                }
            }
            $saf_data->saf_pending_status = $licence_pending;
            $saf_data->update();
            DB::commit();
            return responseMsg(true, $sms, $data);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function readSafDtls($id)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $refWorkflowMstrId     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if (!$refWorkflowMstrId) {
                throw new Exception("Workflow Not Available");
            }
            $init_finish = $this->_common->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $finisher = $init_finish['finisher'];
            $finisher['short_user_name'] = Config::get('TradeConstant.USER-TYPE-SHORT-NAME.' . strtoupper($init_finish['finisher']['role_name']));
            $mUserType      = $this->_common->userType($refWorkflowId);
            $saf_data = PropActiveSaf::find($id);
            if (!$saf_data) {
                throw new Exception("Data not found!....");
            }
            $pendingAt  = $init_finish['initiator']['id'];
            $mlevelData = $this->getLevelData($id);
            if ($mlevelData) {
                $pendingAt = $mlevelData->receiver_role_id;
            }
            $refTimeLine                = $this->getTimelin($id);
            $refApplication = $this->getAllReletedSafId($saf_data->previous_holding_id);
            $mworkflowRoles = $this->_common->getWorkFlowAllRoles($refUserId, $refUlbId, $refWorkflowId, true);
            $mileSton = $this->_common->sortsWorkflowRols($mworkflowRoles);
            $data["userType"]       = $mUserType;
            $data["roles"]          = $mileSton;
            $data["pendingAt"]      = $pendingAt;
            $data['remarks']        = $refTimeLine;
            $data["levelData"]      = $mlevelData;
            $data['finisher']       = $finisher;
            foreach ($refApplication as $key => $val) {
                $data['propertis'][$key]['property']     = $val;
                $data['propertis'][$key]['ownerDtl']       = $this->getOwnereDtlBySId($val->id);
                $data['propertis'][$key]['transactionDtl'] = $this->readTranDtl($val->id);
                $data['propertis'][$key]['documents']      = $this->getSafDocuments($val->id)->map(function ($val2) {
                    $val2->doc_path = !empty(trim($val2->doc_path)) ? $this->readDocumentPath($val2->document_path) : "";
                    return $val2;
                });
            }

            $data = remove_null($data);
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    public function documentUpload(Request $request)
    {
        $refUser = Auth()->user();
        $refUserId = $refUser->id;
        $refUlbId = $refUser->ulb_id;
        $refSafs = null;
        $mUploadDocument = (array)null;
        $mDocumentsList  = (array)null;
        $finalData      = (array)null;
        $finalDocRequired = (array)null;
        $finalOwnerDocRequired = (array)null;
        try {
            $safId = $request->id;
            if (!$safId) {
                throw new Exception("Saf Id Required");
            }
            $refSafs = PropActiveSaf::find($safId);
            if (!$refSafs) {
                throw new Exception("Data Not Found");
            } elseif ($refSafs->doc_verify_status) {
                throw new Exception("Document Verified You Can Not Upload Documents");
            }
            $tempSafs = $this->getAllReletedSaf($refSafs->previous_holding_id);
            foreach ($tempSafs as $key => $val) {
                $requiedDocs     = (array) null;
                $ownersDoc       = (array) null;
                $owneres = $this->getOwnereDtlBySId($val->id);
                $mDocumentsList = $this->getDocumentTypeList($val);
                foreach ($mDocumentsList as $val2) {
                    $doc = (array) null;
                    $doc['docName'] = $val2->doc_type;
                    $doc['isMadatory'] = in_array($val2->doc_type, ["additional_doc", "other"]) ? 0 : 1;
                    $doc['docVal'] = $this->getDocumentList($val2->doc_type);
                    $doc["uploadDoc"] = $this->check_doc_exist($val->id, $val2->doc_type);
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    }
                    array_push($requiedDocs, $doc);
                }
                foreach ($owneres as $key2 => $val2) {
                    $doc = (array) null;
                    $doc["ownerId"]     = $val2->id;
                    $doc["ownerName"]   = $val2->owner_name;
                    $doc['docName']     = "Gender Document";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("gender_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "gender_document");
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    }
                    array_push($ownersDoc, $doc);
                    $doc = (array) null;
                    $doc["ownerId"]     = $val2->id;
                    $doc["ownerName"]   = $val2->owner_name;
                    $doc['docName']     = "DOB Document";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("dob_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "dob_document");
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    }
                    array_push($ownersDoc, $doc);
                    if ($val2->is_armed_force) {
                        $doc = (array) null;
                        $doc["ownerId"]     = $val2->id;
                        $doc["ownerName"]   = $val2->owner_name;
                        $doc['docName']     = "Armed";
                        $doc['isMadatory']  = 1;
                        $doc['docVal']      = $this->getDocumentList("armed_force_document");
                        $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "armed_force_document");
                        if (isset($doc["uploadDoc"]["doc_path"])) {
                            $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                            $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                        }
                        array_push($ownersDoc, $doc);
                    }
                    if ($val2->is_specially_abled) {
                        $doc = (array) null;
                        $doc["ownerId"]     = $val2->id;
                        $doc["ownerName"]   = $val2->owner_name;
                        $doc['docName']     = "Handicap";
                        $doc['isMadatory']  = 1;
                        $doc['docVal']      = $this->getDocumentList("handicaped_document");
                        $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "handicaped_document");
                        if (isset($doc["uploadDoc"]["doc_path"])) {
                            $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                            $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                        }
                        array_push($ownersDoc, $doc);
                    }
                }
                $finalData["properties"][$key]["property"] = $val;
                $finalData["properties"][$key]["owners"] = $owneres;
                $finalData["properties"][$key]["documentsList"] = $requiedDocs;
                $finalData["properties"][$key]["ownersDocList"] = $ownersDoc;
                array_push($finalDocRequired, $requiedDocs);
                array_push($finalOwnerDocRequired, $ownersDoc);
            }
            if ($request->getMethod() == "GET") {
                return responseMsg(true, "", remove_null($finalData));
            }
            if ($request->getMethod() == "POST") {
                DB::beginTransaction();
                $rules = [];
                $message = [];
                $sms = "";
                $documentsList = [];

                $cnt = $request->btn_doc;
                $doc_for = "doc_for$cnt";
                $doc_mstr_id = "doc_mstr_id$cnt";
                $rules = [
                    "safId"             => 'required|digits_between:1,9223372036854775807',
                    'doc' . $cnt          => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                    'doc_for' . $cnt      => "required|string",
                    'doc_mstr_id' . $cnt  => 'required|int',
                    'btn_doc'           => "required"
                ];
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(), $request->all());
                }
                $tempSafId = $request->safId;

                if (!$request->safId || !in_array($request->safId, objToArray(collect($tempSafs)->pluck("id")))) {
                    throw new Exception("Please Enter Valid safId....");
                }
                $saf = $tempSafs->filter(function ($val) use ($tempSafId) {
                    return $val->id == $tempSafId;
                });
                foreach ($saf as $val) {
                    $saf = $val;
                }
                $documentsList = $this->getDocumentTypeList($saf);
                $owneres = $this->getOwnereDtlBySId($request->safId);
                $owners = objToArray($owneres);
                foreach ($owneres as $key2 => $val2) {
                    $doc = (array) null;
                    $doc["ownerId"]     = $val2->id;
                    $doc["ownerName"]   = $val2->owner_name;
                    $doc['docName']     = "Gender Document";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("gender_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "gender_document");
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    }
                    array_push($ownersDoc, $doc);
                    $doc = (array) null;
                    $doc["ownerId"]     = $val2->id;
                    $doc["ownerName"]   = $val2->owner_name;
                    $doc['docName']     = "DOB Document";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("dob_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "dob_document");
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    }
                    array_push($ownersDoc, $doc);
                    if ($val2->is_armed_force) {
                        $doc = (array) null;
                        $doc["ownerId"]     = $val2->id;
                        $doc["ownerName"]   = $val2->owner_name;
                        $doc['docName']     = "Armed";
                        $doc['isMadatory']  = 1;
                        $doc['docVal']      = $this->getDocumentList("armed_force_document");
                        $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "armed_force_document");
                        if (isset($doc["uploadDoc"]["doc_path"])) {
                            $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                            $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                        }
                        array_push($ownersDoc, $doc);
                    }
                    if ($val2->is_specially_abled) {
                        $doc = (array) null;
                        $doc["ownerId"]     = $val2->id;
                        $doc["ownerName"]   = $val2->owner_name;
                        $doc['docName']     = "Handicap";
                        $doc['isMadatory']  = 1;
                        $doc['docVal']      = $this->getDocumentList("handicaped_document");
                        $doc["uploadDoc"]   = $this->check_doc_exist_owner($val->id, $val2->id, "handicaped_document");
                        if (isset($doc["uploadDoc"]["doc_path"])) {
                            $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                            $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                        }
                        array_push($ownersDoc, $doc);
                    }
                }
                $ids = [0];
                $doc_type = "Photo";
                if (in_array($request->$doc_for, objToArray(collect($mDocumentsList)->pluck("doc_type")))) {
                    $ids = objToArray($this->getDocumentList($request->$doc_for)->pluck("id"));
                } elseif (isset($request->$doc_for) &&  in_array($request->$doc_for, objToArray(collect($ownersDoc)->pluck("docName")))) {
                    if ($request->$doc_for == "Gender Document") {
                        $ids = objToArray($this->getDocumentList("gender_document")->pluck("id"));
                        $doc_type = "gender_document";
                    } elseif ($request->$doc_for == "DOB Document") {
                        $ids = objToArray($this->getDocumentList("dob_document")->pluck("id"));
                        $doc_type = "dob_document";
                    } elseif ($request->$doc_for == "Armed") {
                        $ids = objToArray($this->getDocumentList("armed_force_document")->pluck("id"));
                        $doc_type = "armed_force_document";
                    } elseif ($request->$doc_for == "Handicap") {
                        $ids = objToArray($this->getDocumentList("handicaped_document")->pluck("id"));
                        $doc_type = "handicaped_document";
                    }
                }

                # Upload Document 
                if (isset($request->btn_doc) && isset($request->$doc_for) && !in_array($request->$doc_for, ["Gender Document", "DOB Document", "Armed", "Handicap", "Photo"])) {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                    ];
                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist($tempSafId, $request->$doc_for)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }
                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $tempSafId;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }

                            $sms = $request->$doc_for . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $tempSafId;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->save();
                            $sms =  $request->$doc_for . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # Upload Owner Document Gender Document
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Gender Document") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return $val['id'] == $owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($tempSafId, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $tempSafId;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "Gender Document " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $tempSafId;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Gender Document " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # Upload Owner Document DOB Document
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "DOB Document") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return $val['id'] == $owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($tempSafId, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $tempSafId;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "DOB Document " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $tempSafId;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "DOB Document " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # Upload Owner Document is_armfors
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Armed") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return ($val['id'] == $owner_id && $val['is_armed_force']);
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($tempSafId, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {

                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $tempSafId;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "Armed Certificate of " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $tempSafId;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Armed Certificate of " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # Upload Owner Document is_handicap
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Handicap") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return $val['id'] == $owner_id && $val['is_specially_abled'];
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($tempSafId, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {

                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $tempSafId;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }

                            $sms = "Handicap Certificate of " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $tempSafId;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Handicap Certificate of " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # owner image upload hear 
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Photo") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];
                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $req_owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($req_owner_id) {
                        return $val['id'] == $req_owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($tempSafId, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];
                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  0;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $tempSafId;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = 0;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "Photo Of " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $tempSafId;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = 0;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Photo Of " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                } else {
                    throw new Exception("Invalid Document type Passe");
                }

                DB::commit();
                return responseMsg(true, $sms, "");
            }
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    #------------------------------CORE Function ---------------------------------------------
    public function getFlooreDtl($propertyId)
    {
        try {
            $mFloors = PropFloor::select("*")
                ->where("status", 1)
                ->where("property_id", $propertyId)
                ->get();
            return $mFloors;
        } catch (Exception $e) {
            return [];
        }
    }

    public function insertData(Request $req)
    {
        try {
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $mNowDateYm   = Carbon::now()->format("Y-m");
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $mUserType  = $this->_common->userType($refWorkflowId);
            $init_finish = $this->_common->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);

            $assessmentTypeId = $req->assessmentType;
            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if ($req->roadType <= 0)
                $roadWidthType = 4;
            elseif ($req->roadType > 0 && $req->roadType < 20)
                $roadWidthType = 3;
            elseif ($req->roadType >= 20 && $req->roadType <= 39)
                $roadWidthType = 2;
            elseif ($req->roadType > 40)
                $roadWidthType = 1;

            $safCalculation = new SafCalculation();
            $safTaxes = $safCalculation->calculateTax($req);

            $refInitiatorRoleId = $init_finish["initiator"]['id'];                // Get Current Initiator ID
            $initiatorRoleId = $refInitiatorRoleId;
            // dd($request->ward);
            $safNo = $this->safNo($req->ward, $assessmentTypeId, $refUlbId,);
            $saf = new PropActiveSaf();

            // workflows
            $saf->user_id       = $refUserId;
            $saf->workflow_id   = $ulbWorkflowId->id;
            $saf->ulb_id        = $refUlbId;
            $saf->is_aquired    = $req->isAcquired;
            $saf->current_role = $initiatorRoleId;
            $saf->save();
            $safNo = $safNo . "/" . $saf->id;
            $this->tApplySaf($saf, $req, $safNo, $assessmentTypeId, $roadWidthType);                    // Trait SAF Apply
            $saf->update();
            // SAF Owner Details
            if ($req['owner']) {
                $owner_detail = $req['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new PropActiveSafsOwner();
                    $this->tApplySafOwner($owner, $saf, $owner_details);                    // Trait Owner Details
                    $owner->save();
                }
            }

            // Floor Details
            if ($req['floor']) {
                $floor_detail = $req['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new PropActiveSafsFloor();
                    $this->tApplySafFloor($floor, $saf, $floor_details);
                    $floor->save();
                }
            }
            // Property SAF Label Pendings
            // $labelPending = new PropLevelPending();
            // $labelPending->saf_id = $saf->id;
            // $labelPending->receiver_role_id = $initiatorRoleId;
            // $labelPending->save();
            // Insert Tax
            $tax = new InsertTax();
            $tax->insertTax($saf->id, $refUserId, $safTaxes);                                        // Insert SAF Tax
            return $safNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getLevelData(int $safId)
    {
        try {
            $data = PropLevelPending::select("*")
                ->where("saf_id", $safId)
                ->where("status", 1)
                ->where("verification_status", 0)
                ->orderBy("id", "DESC")
                ->first();
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getAllReletedSaf($propertyId)
    {
        try {
            $safs = PropActiveSaf::select("*")
                ->where("previous_holding_id", $propertyId)
                ->orderBy("is_aquired", "desc")
                ->get();
            return $safs;
        } catch (Exception $e) {
            return [];
        }
    }
    public function getDocumentTypeList(PropActiveSaf $application)
    {
        try {
            $docType = ["additional_doc", "property_type", "other"];
            if ($application->prop_type_mstr_id == 1) {
                $docType[] = "super_structure_doc";
            }
            if ($application->property_assessment_id == 3) {
                $docType[] = "transfer_mode";
            }
            // if($application->is_water_harvestin)
            // {
            //     $docType[]="water_harvesting";
            // }
            $data = DB::table("ref_prop_docs_required")
                ->select("doc_id", "doc_type")
                ->where("status", 1)
                ->whereIn("doc_type", $docType)
                ->groupBy("doc_type", "doc_id")
                ->get();
            return $data;
        } catch (Exception $e) {
            $e->getMessage();
            return [];
        }
    }
    public function getDocumentList($doc_type)
    {
        try {
            $data = DB::table("ref_prop_docs_required")
                ->select("id", "doc_name")
                ->where("status", 1)
                ->where("doc_type", $doc_type)
                ->get();
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function check_doc_exist($safNo, $doc_for, $doc_mstr_id = null, $woner_id = null)
    {
        try {
            // DB::enableQueryLog();
            $doc = WfActiveDocument::select(
                "wf_active_documents.id",
                "doc_type",
                "wf_active_documents.verify_status",
                "wf_active_documents.remarks",
                DB::raw("concat(relative_path,'/',image) as doc_path"),
                "doc_mstr_id"
            )
                ->join("ref_prop_docs_required", "ref_prop_docs_required.id", "wf_active_documents.doc_mstr_id")
                ->where('active_id', $safNo)
                ->where('ref_prop_docs_required.doc_type', "$doc_for");
            if ($doc_mstr_id) {
                $doc = $doc->where('doc_mstr_id', $doc_mstr_id);
            }
            if ($woner_id) {
                $doc = $doc->where('owner_dtl_id', $woner_id);
            }
            $doc = $doc->where('wf_active_documents.status', 1)
                ->orderBy('wf_active_documents.id', 'DESC')
                ->first();
            //    dd(DB::getQueryLog());                  
            return $doc;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function check_doc_exist_owner($safNo, $owner_id, $doc_for, $document_id = null)
    {
        try {
            // DB::enableQueryLog();
            $joins = "join";
            if ($doc_for == "Photo") {
                $joins = "leftjoin";
            }
            $doc = DB::table('wf_active_documents as d')
                ->select(
                    "d.id",
                    "d.verify_status",
                    "d.remarks",
                    DB::raw("concat(relative_path,'/',image) as doc_path"),
                    "doc_mstr_id",
                    DB::raw("CASE WHEN ref_prop_docs_required.id ISNULL THEN 'Applicant Image' ELSE doc_type END AS doc_type")
                )
                ->$joins("ref_prop_docs_required", function ($join) use ($doc_for) {
                    $join->on("ref_prop_docs_required.id", "d.doc_mstr_id")
                        ->where('ref_prop_docs_required.doc_type', "$doc_for");
                })
                ->where('active_id', $safNo)
                ->where('owner_dtl_id', $owner_id);
            if ($document_id !== null) {
                $document_id = (int)$document_id;
                $doc = $doc->where('doc_mstr_id', $document_id);
            } else {
                $doc = $doc->where("doc_mstr_id", "<>", 0);
            }
            $doc = $doc->where('d.status', 1)
                ->orderBy('d.id', 'DESC')
                ->first();
            //    print_var(DB::getQueryLog());                    
            return $doc;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function getOwnereDtlBySId($id)
    {
        try {
            $ownerDtl   = PropActiveSafsOwner::select("*")
                ->where("saf_id", $id)
                ->where("status", 1)
                ->get();
            return $ownerDtl;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getSafDocuments($id)
    {
        try {
            $time_line =  WfActiveDocument::select(
                "wf_active_documents.id",
                "image",
                "remarks",
                "verify_status",
                DB::raw(
                    "CASE WHEN ref_prop_docs_required.id ISNULL AND wf_active_documents.owner_dtl_id NOTNULL THEN CONCAT(prop_active_safs_owners.owner_name, ' (Applicant Image)')
                                      WHEN ref_prop_docs_required.id NOTNULL AND wf_active_documents.owner_dtl_id NOTNULL THEN CONCAT(prop_active_safs_owners.owner_name, ' (',doc_type,')')
                                      ELSE doc_type 
                                END AS doc_type"
                )
            )
                ->leftjoin("ref_prop_docs_required", "ref_prop_docs_required.id", "wf_active_documents.doc_mstr_id")
                ->leftjoin("prop_active_safs_owners", "prop_active_safs_owners.id", "wf_active_documents.owner_dtl_id")
                ->where('wf_active_documents.active_id', $id)
                ->where('wf_active_documents.status', 1)
                ->orderBy('wf_active_documents.id', 'desc')
                ->get();
            return $time_line;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getAllReletedSafId($propertyId)
    {
        try {
            $safs = PropActiveSaf::select(
                "prop_active_safs.*",
                "ref_prop_ownership_types.ownership_type",
                "ref_prop_types.property_type",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no")
            )
                ->leftjoin("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "prop_active_safs.ward_mstr_id");
                })
                ->leftjoin("ulb_ward_masters AS new_ward", function ($join) {
                    $join->on("new_ward.id", "=", "prop_active_safs.new_ward_mstr_id");
                })
                ->leftjoin("ref_prop_ownership_types", "ref_prop_ownership_types.id", "prop_active_safs.ownership_type_mstr_id")
                ->leftjoin("ref_prop_types", "ref_prop_types.id", "prop_active_safs.prop_type_mstr_id")
                ->where("prop_active_safs.previous_holding_id", $propertyId)
                ->orderBy("prop_active_safs.is_aquired", "desc")
                ->get();
            return $safs;
        } catch (Exception $e) {
            return [];
        }
    }
    public function readTranDtl($id)
    {
        try {
            $transection   = PropTransaction::select("*")
                ->where("saf_id", $id)
                ->whereIn("status", [1, 2])
                ->get();
            return $transection;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getTimelin($id)
    {
        try {

            $time_line =  PropLevelPending::select(
                "prop_level_pendings.remarks",
                "prop_level_pendings.forward_date",
                "prop_level_pendings.forward_time",
                "prop_level_pendings.receiver_role_id",
                "role_name",
                DB::raw("prop_level_pendings.created_at as receiving_date")
            )
                ->leftjoin(
                    DB::raw(
                        "(SELECT receiver_role_id::bigint, saf_id::bigint, remarks
                                        FROM prop_level_pendings 
                                        WHERE saf_id = $id
                                    )remaks_for"
                    ),
                    function ($join) {
                        $join->on("remaks_for.receiver_role_id", "prop_level_pendings.sender_role_id");
                        // ->where("remaks_for.licence_id","trade_level_pendings.licence_id");
                    }
                )
                ->leftjoin('wf_roles', "wf_roles.id", "prop_level_pendings.receiver_role_id")
                ->where('prop_level_pendings.saf_id', $id)
                ->whereIn('prop_level_pendings.status', [1, 2])
                ->groupBy(
                    'prop_level_pendings.receiver_role_id',
                    'prop_level_pendings.remarks',
                    'prop_level_pendings.forward_date',
                    'prop_level_pendings.forward_time',
                    'wf_roles.role_name',
                    'prop_level_pendings.created_at'
                )
                ->orderBy('prop_level_pendings.created_at', 'desc')
                ->get();
            return $time_line;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . '/api/getImageLink?path=' . $path);
        return $path;
    }
    public function uplodeFile($file, $custumFileName)
    {
        $filePath = $file->storeAs('uploads/Property', $custumFileName, 'public');
        return  $filePath;
    }
    public function getSafDtlById($safId)
    {
        try {
            $saf = PropActiveSaf::select(
                "prop_active_safs.*",
                "ref_prop_types.property_type",
                "ref_prop_ownership_types.ownership_type",
                DB::raw("new_ward.ward_name as new_ward_no,
                                        old_ward.ward_name as ward_no"),
            )
                ->leftjoin("ref_prop_types", "ref_prop_types.id", "prop_active_safs.prop_type_mstr_id")
                ->leftjoin("ref_prop_ownership_types", "ref_prop_ownership_types.id", "prop_active_safs.ownership_type_mstr_id")
                ->leftjoin("ulb_ward_masters AS new_ward", "new_ward.id", "prop_active_safs.new_ward_mstr_id")
                ->leftjoin("ulb_ward_masters AS old_ward", "old_ward.id", "prop_active_safs.ward_mstr_id")
                ->where("prop_active_safs.id", $safId)
                ->first();
            return $saf;
        } catch (Exception $e) {
            return [];
        }
    }

    #-------------------------saf-----------------------
    public function getDocList($request)
    {
        try {
            $refUser = Auth()->user();
            $refUserId = $refUser->id;
            $refUlbId = $refUser->ulb_id;
            $refSafs = null;
            $mUploadDocument = (array)null;
            $mDocumentsList  = (array)null;
            $finalData       = (array)null;
            $requiedDocs     = (array) null;
            $ownersDoc       = (array) null;
            $testOwnersDoc   = (array)null;
            $safId           = $request->applicationId;
            if (!$safId) {
                throw new Exception("Saf Id Required");
            }
            // $refSafs = PropActiveSaf::find($safId);
            $refSafs = $this->getSafDtlById($safId);
            if (!$refSafs) {
                throw new Exception("Data Not Found");
            } elseif ($refSafs->doc_verify_status == 1) {
                throw new Exception("Document Verified You Can Not Upload Documents");
            }
            $mOwneres = $this->getOwnereDtlBySId($refSafs->id);
            $mDocumentsList = $this->getDocumentTypeList($refSafs);
            foreach ($mDocumentsList as $val) {
                $doc = (array) null;
                $doc['docName'] = $val->doc_type;
                $doc['isMadatory'] = in_array($val->doc_type, ["additional_doc", "other"]) ? 0 : 1;
                $doc['docVal'] = $this->getDocumentList($val->doc_type);
                $doc["uploadDoc"] = $this->check_doc_exist($refSafs->saf_no, $val->doc_type);
                array_push($requiedDocs, $doc);
            }
            foreach ($mOwneres as $key => $val) {
                $doc = (array) null;
                $uploadDoc = (array)null;
                $testOwnersDoc[$key] = (array) null;
                $doc["ownerId"]     = $val->id;
                $doc["ownerName"]   = $val->owner_name;
                $doc['docName']     = "Photo";
                $doc['isMadatory']  = 1;
                $doc['docVal'][]      = $this->getDocumentList("owner_photo");
                $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->saf_no, $val->id, "owner_photo");
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
                $mOwneres[$key]["uploadoc"] = $doc["uploadDoc"]->doc_path ?? "";
                $doc = (array) null;
                $doc["ownerId"]     = $val->id;
                $doc["ownerName"]   = $val->owner_name;
                $doc['docName']     = "Gender Document";
                $doc['isMadatory']  = 1;
                $doc['docVal']      = $this->getDocumentList("gender_document");
                $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->saf_no, $val->id, "gender_document");
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
                $doc = (array) null;
                $doc["ownerId"]     = $val->id;
                $doc["ownerName"]   = $val->owner_name;
                $doc['docName']     = "DOB Document";
                $doc['isMadatory']  = 1;
                $doc['docVal']      = $this->getDocumentList("dob_document");
                $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->saf_no, $val->id, "dob_document");
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
                if ($val->is_armed_force) {
                    $doc = (array) null;
                    $doc["ownerId"]     = $val->id;
                    $doc["ownerName"]   = $val->owner_name;
                    $doc['docName']     = "Armed";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("armed_force_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->saf_no, $val->id, "armed_force_document");
                    array_push($ownersDoc, $doc);
                    array_push($testOwnersDoc[$key], $doc);
                }
                if ($val->is_specially_abled) {
                    $doc = (array) null;
                    $doc["ownerId"]     = $val->id;
                    $doc["ownerName"]   = $val->owner_name;
                    $doc['docName']     = "Handicap";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("handicaped_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->saf_no, $val->id, "handicaped_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist($refSafs->id, $val->doc_type);
                    array_push($ownersDoc, $doc);
                    array_push($testOwnersDoc[$key], $doc);
                }
            }
            $data["documentsList"]  = $requiedDocs;
            $data["ownersDocList"]  = $testOwnersDoc[0];
            $data["owners"]         = $mOwneres;

            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    public function safDocumentUpload(Request $request)
    {
        try {
            // $request = collect(mb_convert_encoding($request, "UTF-8", "auto"));
            $refUser = Auth()->user();
            $refUserId = $refUser->id;
            $refUlbId = $refUser->ulb_id;
            $refSafs = null;
            $mUploadDocument = (array)null;
            $mDocumentsList  = (array)null;
            $finalData       = (array)null;
            $requiedDocs     = (array) null;
            $ownersDoc       = (array) null;
            $testOwnersDoc   = (array)null;
            $safId           = $request->id;
            if (!$safId) {
                throw new Exception("Saf Id Required");
            }
            // $refSafs = PropActiveSaf::find($safId);
            $refSafs = $this->getSafDtlById($safId);
            if (!$refSafs) {
                throw new Exception("Data Not Found");
            } elseif ($refSafs->doc_verify_status) {
                throw new Exception("Document Verified You Can Not Upload Documents");
            }
            $mOwneres = $this->getOwnereDtlBySId($refSafs->id);
            $mDocumentsList = $this->getDocumentTypeList($refSafs);
            $mUploadDocument = $this->getSafDocuments($refSafs->id)->map(function ($val) {
                if (isset($val["doc_path"])) {
                    $path = $this->readDocumentPath($val["doc_path"]);
                    $val["doc_path"] = !empty(trim($val["doc_path"])) ? $path : null;
                }
                return $val;
            });
            foreach ($mDocumentsList as $val) {
                $doc = (array) null;
                $doc['docName'] = $val->doc_type;
                $doc['isMadatory'] = in_array($val->doc_type, ["additional_doc", "other"]) ? 0 : 1;
                $doc['docVal'] = $this->getDocumentList($val->doc_type);
                $doc["uploadDoc"] = $this->check_doc_exist($refSafs->id, $val->doc_type);
                if (isset($doc["uploadDoc"]["doc_path"])) {
                    $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                    $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                }
                array_push($requiedDocs, $doc);
            }
            foreach ($mOwneres as $key => $val) {
                $doc = (array) null;
                $uploadDoc = (array)null;
                $testOwnersDoc[$key] = (array) null;
                $doc["ownerId"]     = $val->id;
                $doc["ownerName"]   = $val->owner_name;
                $doc['docName']     = "Photo";
                $doc['isMadatory']  = 1;
                $doc['docVal'][]      = ["id" => 0, "doc_name" => "Photo"];
                $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->id, $val->id, "Photo", 0);
                if (isset($doc["uploadDoc"]["doc_path"])) {
                    $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                    $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    array_push($uploadDoc, $doc["uploadDoc"]);
                }
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
                $doc = (array) null;
                $doc["ownerId"]     = $val->id;
                $doc["ownerName"]   = $val->owner_name;
                $doc['docName']     = "Gender Document";
                $doc['isMadatory']  = 1;
                $doc['docVal']      = $this->getDocumentList("gender_document");
                $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->id, $val->id, "gender_document");
                if (isset($doc["uploadDoc"]["doc_path"])) {
                    $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                    $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    array_push($uploadDoc, $doc["uploadDoc"]);
                }
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
                $doc = (array) null;
                $doc["ownerId"]     = $val->id;
                $doc["ownerName"]   = $val->owner_name;
                $doc['docName']     = "DOB Document";
                $doc['isMadatory']  = 1;
                $doc['docVal']      = $this->getDocumentList("dob_document");
                $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->id, $val->id, "dob_document");
                if (isset($doc["uploadDoc"]["doc_path"])) {
                    $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                    $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                    array_push($uploadDoc, $doc["uploadDoc"]);
                }
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
                if ($val->is_armed_force) {
                    $doc = (array) null;
                    $doc["ownerId"]     = $val->id;
                    $doc["ownerName"]   = $val->owner_name;
                    $doc['docName']     = "Armed";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("armed_force_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->id, $val->id, "armed_force_document");
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                        array_push($uploadDoc, $doc["uploadDoc"]);
                    }
                    array_push($ownersDoc, $doc);
                    array_push($testOwnersDoc[$key], $doc);
                }
                if ($val->is_specially_abled) {
                    $doc = (array) null;
                    $doc["ownerId"]     = $val->id;
                    $doc["ownerName"]   = $val->owner_name;
                    $doc['docName']     = "Handicap";
                    $doc['isMadatory']  = 1;
                    $doc['docVal']      = $this->getDocumentList("handicaped_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist_owner($refSafs->id, $val->id, "handicaped_document");
                    $doc["uploadDoc"]   = $this->check_doc_exist($refSafs->id, $val->doc_type);
                    if (isset($doc["uploadDoc"]["doc_path"])) {
                        $path = $this->readDocumentPath($doc["uploadDoc"]["doc_path"]);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($doc["uploadDoc"]["doc_path"])) ? $path : null;
                        array_push($uploadDoc, $doc["uploadDoc"]);
                    }
                    array_push($ownersDoc, $doc);
                    array_push($testOwnersDoc[$key], $doc);
                }
                $mOwneres[$key]["uploadoc"] = collect($uploadDoc);
            }
            $data["documentsList"]  = $requiedDocs;
            $data["ownersDocList"]  = $testOwnersDoc;
            $data["safDtl"]         = $refSafs;
            $data["owners"]         = $mOwneres;
            $data["uploadDocument"] = $mUploadDocument;
            if ($request->getMethod() == "GET") {
                return responseMsg(true, "", remove_null($data));
            }
            if ($request->getMethod() == "POST") {
                DB::beginTransaction();
                $rules = [];
                $message = [];
                $sms = "";
                $owners = objToArray($mOwneres);
                $cnt = $request->btn_doc;
                $doc_for = "doc_for$cnt";
                $doc_mstr_id = "doc_mstr_id$cnt";
                $rules = [
                    'doc' . $cnt         => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                    'doc_for' . $cnt     => "required|string",
                    'doc_mstr_id' . $cnt => 'required|int',
                    'btn_doc'          => "required",
                ];
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(), $request->all());
                }
                // dd($request->safId,"saf");
                $ids = [0];
                $doc_type = "Photo";
                if (in_array($request->$doc_for, objToArray(collect($mDocumentsList)->pluck("doc_type")))) {
                    $ids = objToArray($this->getDocumentList($request->$doc_for)->pluck("id"));
                } elseif (isset($request->$doc_for) &&  in_array($request->$doc_for, objToArray(collect($ownersDoc)->pluck("docName")))) {
                    if ($request->$doc_for == "Gender Document") {
                        $ids = objToArray($this->getDocumentList("gender_document")->pluck("id"));
                        $doc_type = "gender_document";
                    } elseif ($request->$doc_for == "DOB Document") {
                        $ids = objToArray($this->getDocumentList("dob_document")->pluck("id"));
                        $doc_type = "dob_document";
                    } elseif ($request->$doc_for == "Armed") {
                        $ids = objToArray($this->getDocumentList("armed_force_document")->pluck("id"));
                        $doc_type = "armed_force_document";
                    } elseif ($request->$doc_for == "Handicap") {
                        $ids = objToArray($this->getDocumentList("handicaped_document")->pluck("id"));
                        $doc_type = "handicaped_document";
                    }
                }

                # Upload Document 
                if (isset($request->btn_doc) && isset($request->$doc_for) && !in_array($request->$doc_for, ["Gender Document", "DOB Document", "Armed", "Handicap", "Photo"])) {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                    ];
                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist($refSafs->id, $request->$doc_for)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }
                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id = $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $refSafs->id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }

                            $sms = $request->$doc_for . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $refSafs->id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->save();
                            $sms =  $request->$doc_for . " Upload Successfully";
                        }
                    } else {
                        throw new Exception("something errors in Document Uploades");
                    }
                }
                # Upload Owner Document Gender Document
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Gender Document") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return $val['id'] == $owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($refSafs->id, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $refSafs->id;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "Gender Document " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $refSafs->id;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Gender Document " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        throw new Exception("something errors in Document Uploades");
                    }
                }
                # Upload Owner Document DOB Document
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "DOB Document") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return $val['id'] == $owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($refSafs->id, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $refSafs->id;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "DOB Document " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $refSafs->id;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "DOB Document " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        throw new Exception("something errors in Document Uploades");
                    }
                }
                # Upload Owner Document is_armfors
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Armed") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return ($val['id'] == $owner_id && $val['is_armed_force']);
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($refSafs->id, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {

                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $refSafs->id;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "Armed Certificate of " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $refSafs->id;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Armed Certificate of " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        throw new Exception("something errors in Document Uploades");
                    }
                }
                # Upload Owner Document is_handicap
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Handicap") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt . '' => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($owner_id) {
                        return $val['id'] == $owner_id && $val['is_specially_abled'];
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    $doc_mstr_id = "doc_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($refSafs->id, $request->owner_id, $doc_type)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {

                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $refSafs->id;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }

                            $sms = "Handicap Certificate of " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $refSafs->id;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = $request->$doc_mstr_id;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Handicap Certificate of " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        throw new Exception("something errors in Document Uploades");
                    }
                }
                # owner image upload hear 
                elseif (isset($request->btn_doc) && isset($request->$doc_for) && $request->$doc_for == "Photo") {
                    $rules = [
                        'doc' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_for' . $cnt => "required|string",
                        'doc_mstr_id' . $cnt => 'required|int',
                        "owner_id" => "required|digits_between:1,9223372036854775807"
                    ];
                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $req_owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($req_owner_id) {
                        return $val['id'] == $req_owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc' . $cnt);
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($refSafs->id, $request->owner_id, $doc_type, $request->$doc_mstr_id)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['doc_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];
                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->doc_path =  $filePath;
                                $app_doc_dtl_id->doc_mstr_id =  0;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $propDocs = new PropActiveSafsDoc;
                                $propDocs->saf_id = $refSafs->id;
                                $propDocs->saf_owner_dtl_id = $request->owner_id;
                                $propDocs->doc_mstr_id = 0;
                                $propDocs->user_id = $refUserId;

                                $propDocs->save();
                                $newFileName = $propDocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "saf_owner_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $propDocs->doc_path =  $filePath;
                                $propDocs->update();
                            }
                            $sms = "Photo Of " . $woner_id['owner_name'] . " Update Successfully";
                        } else {
                            $propDocs = new PropActiveSafsDoc;
                            $propDocs->saf_id = $refSafs->id;
                            $propDocs->saf_owner_dtl_id = $request->owner_id;
                            $propDocs->doc_mstr_id = 0;
                            $propDocs->user_id = $refUserId;

                            $propDocs->save();
                            $newFileName = $propDocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "saf_owner_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $propDocs->doc_path =  $filePath;
                            $propDocs->update();
                            $sms = "Photo Of " . $woner_id['owner_name'] . " Upload Successfully";
                        }
                    } else {
                        throw new Exception("something errors in Document Uploades");
                    }
                } else {
                    throw new Exception("Invalid Document type Passe");
                }
                DB::commit();
                $mUploadDocument = $this->getSafDocuments($refSafs->id)->map(function ($val) {
                    if (isset($val["doc_path"])) {
                        $path = $this->readDocumentPath($val["doc_path"]);
                        $val["doc_path"] = !empty(trim($val["doc_path"])) ? $path : null;
                    }
                    return $val;
                });
                $data["uploadDocument"] = $mUploadDocument;
                return responseMsg(true, $sms, $data["uploadDocument"]);
            }
        } catch (Exception $e) {
            dd($e->getline(), $e->getFile());
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function getUploadDocuments($request)
    {
        try {
            $refUser = Auth()->user();
            $refUserId = $refUser->id;
            $refUlbId = $refUser->ulb_id;
            $refSafs = null;
            $mUploadDocument = (array)null;
            $mDocumentsList  = (array)null;
            $finalData       = (array)null;
            $requiedDocs     = (array) null;
            $ownersDoc       = (array) null;
            $applicationId           = $request->applicationId;
            if (!$applicationId) {
                throw new Exception("Application Id Required");
            }
            $refSafs = PropActiveSaf::find($applicationId);
            if (!$refSafs) {
                throw new Exception("Data Not Found");
            }
            $mUploadDocument = $this->getSafDocuments($refSafs->id)->map(function ($val) {
                if (isset($val["doc_path"])) {
                    $path = $this->readDocumentPath($val["doc_path"]);
                    $val["doc_path"] = !empty(trim($val["doc_path"])) ? $path : null;
                }
                return $val;
            });
            $data["uploadDocument"] = $mUploadDocument;
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    #-------------------------End saf-----------------------
    #-------------------------Citize------------------------
    public function CitizenPymentHistory(Request $request)
    {
        try {
            $refUser = Auth()->user();
            $refUserId   = $refUser->id;
            $refUlbId    = $refUser->ulb_id;
            $refTran     = null;
            $propTran    = PropActiveSaf::select(
                "prop_active_safs.saf_no",
                "prop_active_safs.application_date",
                "prop_transactions.tran_no",
                "prop_transactions.tran_date",
                "prop_transactions.payment_mode",
                "prop_transactions.amount",
                "prop_active_safs.id",
                DB::raw("prop_transactions.id As tran_id,
                                                    'active_saf' AS type
                                                ")
            )
                ->join("prop_transactions", "prop_transactions.saf_id", "prop_active_safs.id")
                ->whereIn("prop_transactions.status", [1, 2])
                ->where("prop_active_safs.status", 1)
                ->where("prop_active_safs.user_id", $refUserId)
                ->where("prop_active_safs.ulb_id", $refUlbId)
                ->union(
                    DB::table("prop_safs")->select(
                        "prop_safs.saf_no",
                        "prop_safs.application_date",
                        "prop_transactions.tran_no",
                        "prop_transactions.tran_date",
                        "prop_transactions.payment_mode",
                        "prop_transactions.amount",
                        DB::raw("prop_safs.id,
                                                    prop_transactions.id As tran_id,
                                                    'saf' AS type 
                                                ")
                    )
                        ->join("prop_transactions", "prop_transactions.saf_id", "prop_safs.id")
                        ->whereIn("prop_transactions.status", [1, 2])
                        ->where("prop_safs.status", 1)
                        ->where("prop_safs.user_id", $refUserId)
                        ->where("prop_safs.ulb_id", $refUlbId)
                )
                ->union(
                    PropProperty::select(
                        DB::raw("prop_properties.new_holding_no as saf_no,
                                                            prop_properties.application_date"),
                        "prop_transactions.tran_no",
                        "prop_transactions.tran_date",
                        "prop_transactions.payment_mode",
                        "prop_transactions.amount",
                        DB::raw("prop_properties.id ,
                                                                    prop_transactions.id As tran_id,
                                                                    'property' AS type 
                                                                ")
                    )
                        ->join("prop_transactions", "prop_transactions.property_id", "prop_properties.id")
                        ->whereIn("prop_transactions.status", [1, 2])
                        ->where("prop_properties.status", 1)
                        ->where("prop_properties.user_id", $refUserId)
                        ->where("prop_properties.ulb_id", $refUlbId)
                )
                ->get();
            $tradeTran = ActiveLicence::select(
                "active_licences.application_no",
                "active_licences.license_no",
                "active_licences.provisional_license_no",
                "active_licences.apply_date",
                "trade_transactions.transaction_no",
                "trade_transactions.transaction_date",
                "trade_transactions.payment_mode",
                "trade_transactions.paid_amount",
                DB::raw("active_licences.id, 
                                        trade_transactions.id as tran_id"),
            )
                ->join("trade_transactions", "trade_transactions.related_id", "active_licences.id")
                ->whereIn("trade_transactions.status", [1, 2])
                ->where("active_licences.status", 1)
                ->where("active_licences.user_id", $refUserId)
                ->where("active_licences.ulb_id", $refUlbId)
                ->union(
                    ExpireLicence::select(
                        "expire_licences.application_no",
                        "expire_licences.license_no",
                        "expire_licences.provisional_license_no",
                        "expire_licences.apply_date",
                        "trade_transactions.transaction_no",
                        "trade_transactions.transaction_date",
                        "trade_transactions.payment_mode",
                        "trade_transactions.paid_amount",
                        DB::raw("expire_licences.id, 
                                        trade_transactions.id as tran_id"),
                    )
                        ->join("trade_transactions", "trade_transactions.related_id", "expire_licences.id")
                        ->whereIn("trade_transactions.status", [1, 2])
                        ->where("expire_licences.status", 1)
                        ->where("expire_licences.user_id", $refUserId)
                        ->where("expire_licences.ulb_id", $refUlbId)
                )
                ->get();
            $data["property"] = $propTran;
            $data["trade"] = $tradeTran;
            $data["water"] = [];

            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    #-------------------------End Citize--------------------
}

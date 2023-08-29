<?php

namespace App\Repository\Notice;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Service\IdGeneratorController;
use App\MicroServices\DocUpload;
use App\Models\CustomDetail;
use App\Models\Notice\NoticeApplication;
use App\Models\Notice\NoticeReminder;
use App\Models\Notice\NoticeSedule;
use App\Models\Workflows\WfRole;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Auth;
use App\Traits\Notice\NoticeTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Str;

/**
 * Created By Sandeep Bara
 * Date 2023-03-027
 * Notice Module
 */

 class Notice implements INotice
 {
    use Auth;
    use Workflow;
    use NoticeTrait;

    private $_DB;
    private $_DB_NAME;
    private $_COMMON_FUNCTION;
    private $_MODEL_Workflow_Tracks;
    private $_MODEL_CUSTOM_DETAIL;
    private $_WORKFLOW_MAP;

    private $_WF_MASTER_ID;
    protected $_GENERAL_NOTICE_WF_MASTER_Id;
    protected $_DENIAL_NOTICE_WF_MASTER_Id;
    protected $_PAYMENT_NOTICE_WF_MASTER_Id;
    protected $_ILLEGAL_OCCUPATION_WF_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_NOTICE_CONSTAINT;
    protected $_DOC_PATH;
    protected $_NOTICE_TYPE;
    protected $_MODULE_CONSTAINT;
    protected $_ID_GENERATOR;
    protected $_APPLICATION_NO_CONST;
    protected $_NOTICE_NO_CONST;

    public function __construct()
    {
        $this->_DB_NAME = "pgsql_notice";
        $this->_DB = DB::connection( $this->_DB_NAME );
        DB::enableQueryLog();
        $this->_DB->enableQueryLog();

        $this->_COMMON_FUNCTION             =   new CommonFunction();
        $this->_ID_GENERATOR                =   new IdGeneratorController();
        $this->_MODEL_Workflow_Tracks       = new WorkflowTrack();
        $this->_MODEL_CUSTOM_DETAIL         = new CustomDetail();
        $this->_WORKFLOW_MAP                 = new WorkflowMap();

        $this->_GENERAL_NOTICE_WF_MASTER_Id =   Config::get('workflow-constants.GENERAL_NOTICE_MASTER_ID');
        $this->_DENIAL_NOTICE_WF_MASTER_Id  =   Config::get('workflow-constants.DENIAL_NOTICE_MASTER_ID');;
        $this->_PAYMENT_NOTICE_WF_MASTER_Id =   Config::get('workflow-constants.PAYMENT_NOTICE_MASTER_ID');
        $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id = Config::get('workflow-constants.ILLEGAL_OCCUPATION_NOTICE_MASTER_ID');
        $this->_MODULE_CONSTAINT            =   Config::get('module-constants');
        $this->_MODULE_ID                   =   Config::get('module-constants.NOTICE_MASTER_ID');
        $this->_NOTICE_CONSTAINT            =   Config::get("NoticeConstaint");

        $this->_REF_TABLE                   =   $this->_NOTICE_CONSTAINT["NOTICE_REF_TABLE"]??null;
        $this->_APPLICATION_NO_CONST        =   $this->_NOTICE_CONSTAINT["APPLICATION_NO_GENERATOR_ID"]??null;
        $this->_NOTICE_NO_CONST             =   $this->_NOTICE_CONSTAINT["NOTICE_NO_GENERATOR_ID"]??null;
        $this->_DOC_PATH                    =   $this->_NOTICE_CONSTAINT["NOTICE_RELATIVE_PATH"]??null;
        $this->_NOTICE_TYPE                 =   $this->_NOTICE_CONSTAINT["NOTICE-TYPE"]??null;
        $this->_WF_MASTER_ID                =   null;
        
        
    }

    public function begin()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::beginTransaction();
        if($db1!=$db2 )
        $this->_DB->beginTransaction();
    }
    public function rollback()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::rollBack();
        if($db1!=$db2 )
        $this->_DB->rollBack();
    }
     
    public function commit()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::commit();
        if($db1!=$db2 )
        $this->_DB->commit();
    }

    public function add(Request $request)
    {
        $user = Auth()->user();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        $notice_type_id = null;
        try{
            $data = array();
            if($request->noticeType==1)
            {
                $this->_WF_MASTER_ID = $this->_GENERAL_NOTICE_WF_MASTER_Id;                
            }
            elseif($request->noticeType==2)
            {
                $this->_WF_MASTER_ID = $this->_DENIAL_NOTICE_WF_MASTER_Id;
            }
            elseif($request->noticeType==3)
            {
                $this->_WF_MASTER_ID = $this->_PAYMENT_NOTICE_WF_MASTER_Id;
            }
            elseif($request->noticeType==4)
            {
                $this->_WF_MASTER_ID = $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id;
            }
            $notice_for_module_id=$this->_NOTICE_CONSTAINT["NOTICE-MODULE"][strtoupper($request->moduleName)]??null;
            if(!$this->_WF_MASTER_ID)
            {
                throw new Exception("Workflow Not Avalable");
            }
            if(!$notice_for_module_id)
            {
                throw new Exception("Enter Valide Module Name");
            }
            $notice_type_id = $request->noticeType??NULL;
            $notice_type = $this->_NOTICE_CONSTAINT["NOTICE-TYPE-BY-ID"][$notice_type_id]??null;
            $refWorkflows  = $this->_COMMON_FUNCTION->iniatorFinisher($userId, $ulbId, $this->_WF_MASTER_ID);
            // dd(DB::getQueryLog());
            $this->begin();
            $noticeApplication = new NoticeApplication();
            $noticeApplication->notice_type_id  = $notice_type_id;
            $noticeApplication->notice_for_module_id  = $notice_for_module_id;
            $noticeApplication->application_id  = $request->applicationId??NULL;
            if($request->applicationId && $request->moduleId)
            {
                $noticeApplication->module_id  = $request->moduleId;
                $noticeApplication->module_type  = $request->moduleType;
            }
            $noticeApplication->firm_name       = $request->firmName;
            $noticeApplication->ptn_no          = $request->ptnNo;
            $noticeApplication->holding_no      = $request->holdingNo;
            $noticeApplication->license_no      = $request->licenseNo;
            $noticeApplication->served_to       = $request->servedTo;
            $noticeApplication->address         = $request->address;
            $noticeApplication->locality        = $request->locality;
            $noticeApplication->mobile_no       = $request->mobileNo;
            $noticeApplication->owner_name      = $request->ownerName;
            $noticeApplication->notice_content  = $request->noticeDescription;
            $noticeApplication->initater_role   = $refWorkflows["initiator"]["id"];
            $noticeApplication->current_role    = $refWorkflows["initiator"]["id"];
            $noticeApplication->finisher_role    = $refWorkflows["finisher"]["id"];
            $noticeApplication->workflow_id     = $this->_WF_MASTER_ID;
            $noticeApplication->user_id         = $userId;
            $noticeApplication->ulb_id          = $ulbId;
            $id_request = new Request(["ulbId"=>$ulbId,"paramId"=>$this->_APPLICATION_NO_CONST]);
            $id_respons = $this->_ID_GENERATOR->idGenerator($id_request);
            $noticeApplication->application_no  = $id_respons->original["data"];
            $noticeApplication->save();
            $applicationNo =  $noticeApplication->application_no ;
            $notice_id = $noticeApplication->id;
            if ($notice_id && $request->document) 
            {
                $docUpload = new DocUpload;
                $refImageName = $notice_type;
                $refImageName = $notice_id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->document;
                $imageName = $docUpload->upload($refImageName, $document, $this->_DOC_PATH);

                $noticeApplication->documents = $this->_DOC_PATH."/".$imageName;
                $noticeApplication->save();

            }
            $message="Notice Apply Successfully. Your Notice Application No. is: $applicationNo";
            $data["ApplicationNo"]=$applicationNo;
            
            if($noticeApplication->initater_role==$noticeApplication->finisher_role )
            {
                $metaReqs["applicationId"] = $notice_id;
                $metaReqs["status"] = 1;
                $metaReqs = new Request($metaReqs);
                $role = $this->_COMMON_FUNCTION->getUserRoll($userId,$ulbId,$this->_WF_MASTER_ID);
                $noticeApplication->current_role    = ($role->role_id??0);    
                $noticeApplication->update();

                $response = $this->approveReject($metaReqs);                
                $noticeApplication->current_role    = $refWorkflows["initiator"]["id"];
                $noticeApplication->update();
                $message = $response->original["message"];
                if(!$response->original["status"])
                {
                    throw new Exception($message);
                }
                $data = $response->original["data"];
            }
            
            $this->commit();
            return  responseMsg(true, $message, $data);

        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function noticeList(Request $request)
    {        
        try{
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            if(!in_array(strtoupper($request->moduleName),$this->_NOTICE_CONSTAINT["MODULE-TYPE"]))
            {
                throw new Exception("Invalide Module");
            }
            $notice_for_module_id=$this->_NOTICE_CONSTAINT["NOTICE-MODULE"][strtoupper($request->moduleName)]??null;
            $request->request->add(["moduleId"=>$notice_for_module_id]);   

            $notice = NoticeApplication::select(
                        "notice_applications.id",
                        "notice_applications.application_no",
                        "notice_applications.notice_type_id",
                        "notice_applications.notice_no",
                        "notice_applications.notice_date",
                        "notice_applications.notice_state",
                        "notice_applications.application_id",
                        "notice_applications.module_id",
                        "notice_applications.module_type",
                        "notice_applications.firm_name",
                        "notice_applications.ptn_no",
                        "notice_applications.holding_no",
                        "notice_applications.license_no",                        
                        "notice_applications.served_to",
                        "notice_applications.address",
                        "notice_applications.locality",
                        "notice_applications.mobile_no",
                        "notice_applications.notice_content",
                        "notice_applications.owner_name",
                        "notice_applications.documents",
                        "notice_applications.status",
                        "notice_type_masters.notice_type",
                        DB::raw("cast(notice_applications.created_at as date) as apply_date")
                    )
                    ->join("notice_type_masters","notice_type_masters.id","notice_applications.notice_type_id")
                    ->where("notice_applications.ulb_id",$ulb_id)
                    ->where("notice_applications.status","<>",0)
                    ->where("notice_applications.notice_for_module_id",$request->moduleId)
                    ->get();
            $data["application"] = $notice;
            switch(strtoupper($request->keyWord))
            {
                case "APPROVE" : $data["application"]   =  $notice->where("status",5);
                                 break;
                case "REJECT" : $data["application"]    =   $notice->where("status",4);
                                 break;
                case "GENERAL" : $data["application"]   =  $notice->where("notice_type_id",($this->_NOTICE_TYPE["GENERAL NOTICE"]??0));
                                 break;
                case "DENIAL" : $data["application"]    =   $notice->where("notice_type_id",($this->_NOTICE_TYPE["DENIAL NOTICE"]??0));
                                 break;
                case "PAYMENT" : $data["application"]   =   $notice->where("notice_type_id",($this->_NOTICE_TYPE["PAYMENT RELATED NOTICE"]??0));
                                 break;
                case "ILLEGAL" : $data["application"]   =   $notice->where("notice_type_id",($this->_NOTICE_TYPE["ILLEGAL OCCUPATION NOTICE"]??0));
                                 break;
            }
            
            $data["application"] = ($data["application"]->values())->toArray();
            $data["total_notice"] = $notice->count();
            $data["total_approved_notice"] = $notice->where("status",5)->count();
            $data["total_rejected_notice"] = $notice->where("status",4)->count();
            $data["total_general_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["GENERAL NOTICE"]??0))->count();
            $data["total_denial_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["DENIAL NOTICE"]??0))->count();
            $data["total_payment_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["PAYMENT RELATED NOTICE"]??0))->count();
            $data["total_illegal_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["ILLEGAL OCCUPATION NOTICE"]??0))->count();
            return responseMsg(true, "",  remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function noticeView(Request $request)
    {
        try{
            $user = Auth()->user();
            $ulb_id = $user->ulb_id??0;
            $notice = NoticeApplication::select(
                "notice_applications.*",
                "notice_type_masters.notice_type",
                DB::raw("cast(notice_applications.created_at as date) as apply_date"),
            )
            ->join("notice_type_masters","notice_type_masters.id","notice_applications.notice_type_id")
            ->where("notice_applications.ulb_id",$ulb_id)
            ->where("notice_applications.status","<>",0)
            ->where("notice_applications.id",$request->applicationId)
            ->first();
            if(!$notice)
            {
                throw new Exception("Data Not Found");
            }           
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" =>  $this->generateBasicDetails($notice)      // Trait function to get Basic Details
            ];
            $addressElement=[];
            switch($notice->notice_for_module_id)   
            {
                #property
                case 1:  $addressElement = [
                            'headerTitle' => "Property & Address",
                            'data' => $cardDetails = $this->generateProperty($notice)
                        ];
                        break;
                #water
                case 2:  $addressElement = [
                            'headerTitle' => "Property & Address",
                            'data' => $cardDetails = $this->generateWater($notice)
                        ];
                        break;
                #tade
                case 3:  $addressElement = [
                            'headerTitle' => "Property & Address",
                            'data' => $cardDetails = $this->generateTrade($notice)
                        ];
                        break;
                #SWM
                case 4:  $addressElement = [
                            'headerTitle' => "Property & Address",
                            'data' => $cardDetails = $this->generateProperty($notice)
                        ];
                        break;
                #ADVERTISEMENT
                case 5:  $addressElement = [
                            'headerTitle' => "Property & Address",
                            'data' => $cardDetails = $this->generateProperty($notice)
                        ];
                        break;
                // case 6:  $addressElement = [
                //             'headerTitle' => "Property & Address",
                //             'data' => $cardDetails = $this->generateProperty($notice)
                //         ];
                //         break;
                default : throw new Exception("Invalid Module");
            }  
            $cardDetails = $this->generateCardDetails($notice);
            $cardElement = [
                'headerTitle' => "Status:",
                'data' => $cardDetails
            ];
            $mStatus = $this->applicationStatus($request->applicationId);
            $data = $notice->toArray();
            $data['timeline'] = [];
            $data['fullDetailsData']['dataArray'] = new Collection([$basicElement,$addressElement]);
            $data['fullDetailsData']['cardArray'] = new Collection([$cardElement]);

            $levelComment = $this->_MODEL_Workflow_Tracks->getTracksByRefId($this->_REF_TABLE, $notice->id);
            $data['levelComment'] = $levelComment;
            $citizenComment = $this->_MODEL_Workflow_Tracks->getCitizenTracks($this->_REF_TABLE, $notice->id, $notice->user_id??0);
            
            $data['citizenComment'] = $citizenComment;
            
            $metaReqs['customFor']  = 'Notice';
            $metaReqs['wfRoleId']   = $notice->current_role;
            $metaReqs['workflowId'] = $notice->workflow_id;
            $metaReqs['lastRoleId'] = $notice->finisher_role;
            
            $request->request->add($metaReqs);            
           
            $forwardBackward = $this->_WORKFLOW_MAP->getRoleDetails($request);
            $data['roleDetails'] = collect($forwardBackward)['original']['data'];
            $data['timelineData'] = collect($request);

            $custom = $this->_MODEL_CUSTOM_DETAIL->getCustomDetails($request);
            $data['departmentalPost'] = collect($custom)['original']['data'];
            return responseMsg(true, 'Data Fetched', remove_null($data));
           
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function fullDtlById(Request $request)
    {
        try{ 
            $refUser        = Auth()->user(); 
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id ?? 0;
            $notice = NoticeApplication::select(
                "notice_applications.*",
                "notice_type_masters.notice_type",
                DB::raw("cast(notice_applications.created_at as date) as apply_date"),
            )
            ->join("notice_type_masters","notice_type_masters.id","notice_applications.notice_type_id")            
            ->find($request->applicationId);
            if(!$notice)
            {
                throw new Exception("Data Not Found");
            } 
            $refUlbId = $notice->ulb_id;
            $refWorkflowId=$notice->workflow_id;
            $mStatus = $this->applicationStatus($notice->id);            
            $refTimeLine    = $this->getTimelin($notice->id);
            $mUserType      = $this->_COMMON_FUNCTION->userType($refWorkflowId);

            $mworkflowRoles = $this->_COMMON_FUNCTION->getWorkFlowAllRoles($refUserId, $refUlbId, $refWorkflowId, true);
            $mileSton = $this->_COMMON_FUNCTION->sortsWorkflowRols($mworkflowRoles);

            $init_finish = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            
            $finisher = $init_finish['finisher']??[];
            $pendingAt  = $init_finish['initiator']['id']??0;
            if (!$refTimeLine->isEmpty()) {
                $pendingAt = $refTimeLine->receiver_role_id;
            }
            $noticeRemider = $this->getAllNoticRemider($notice->id);
            $data['noticeDtl']     = $notice;
            $data['sendNotice']     = $noticeRemider;
            $data['pendingStatus']  = $mStatus;
            $data['remarks']        = $refTimeLine;
            $data["userType"]       = $mUserType;
            $data["roles"]          = $mileSton;
            $data["pendingAt"]      = $pendingAt;
            $data['finisher']       = $finisher;
            return responseMsg(true, "", remove_null($data));        
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function WorkFlowMetaList()
    {
        return $this->_DB->table("notice_applications")
        ->join("notice_type_masters","notice_type_masters.id","notice_applications.notice_type_id")
        ->join("module_masters","module_masters.id","notice_applications.notice_for_module_id")
        ->select(
            "notice_applications.id",
            "notice_applications.application_no",
            "notice_applications.firm_name",
            "notice_applications.ptn_no",
            "notice_applications.holding_no",
            "notice_applications.license_no",                        
            "notice_applications.served_to",
            "notice_applications.address",
            "notice_applications.locality",
            "notice_applications.mobile_no",
            "notice_applications.notice_content",
            "notice_applications.owner_name",
            "notice_type_masters.notice_type",
            "module_masters.module_name",
            DB::raw("cast(notice_applications.created_at as date) as apply_date")
        );
    }
    public function inbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
           
            $role1 = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $this->_GENERAL_NOTICE_WF_MASTER_Id)->role_id??0;
            $role2 = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $this->_PAYMENT_NOTICE_WF_MASTER_Id)->role_id??0;
            $role3 = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id)->role_id??0;
            
            $inputs = $request->all();       
            $application = $this->WorkFlowMetaList()
                ->where("notice_applications.ulb_id",$refUlbId)
                ->whereNOTIN("notice_applications.status",[0,5])    
                ->where(function($where)use($role1,$role2,$role3){
                    $where->ORWHERE(function($where2)use($role1){
                        $where2->where("notice_applications.current_role", $role1)
                        ->where("notice_applications.workflow_id",$this->_GENERAL_NOTICE_WF_MASTER_Id);
                    })
                    ->ORWHERE(function($where2)use($role2){
                        $where2->where("notice_applications.current_role", $role2)
                        ->where("notice_applications.workflow_id",$this->_PAYMENT_NOTICE_WF_MASTER_Id);
                    })
                    ->ORWHERE(function($where2)use($role3){
                        $where2->where("notice_applications.current_role", $role3)
                        ->where("notice_applications.workflow_id",$this->_ILLEGAL_OCCUPATION_WF_MASTER_Id);
                    });
                });
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $application = $application->where(function ($query) use ($key) {
                    $query->orwhere('notice_applications.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.ptn_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("notice_applications.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("notice_applications.firm_name", 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $mWardIds = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) 
            {
                $application = $application
                    ->whereBetween(DB::raw('cast(notice_applications.application_date as date)'), [$inputs['formDate'], $inputs['formDate']]);
            }
            if($request->all)
            {
                $application= $application->get();
                return responseMsg(true, "", $application);
            } 
            $perPage = $request->perPage ? $request->perPage :  10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $paginator = $application->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ]; 
            return responseMsg(true, "", remove_null($list)); 
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    } 

    public function outbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
           
            $role1 = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $this->_GENERAL_NOTICE_WF_MASTER_Id)->role_id??0;
            $role2 = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $this->_PAYMENT_NOTICE_WF_MASTER_Id)->role_id??0;
            $role3 = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id)->role_id??0;
            
            $inputs = $request->all();
            // DB::enableQueryLog();          
            $application = $this->WorkFlowMetaList()
                ->where("notice_applications.ulb_id",$refUlbId)
                ->whereNOTIN("notice_applications.status",[0,5])
                ->where(function($where)use($role1,$role2,$role3){
                    $where->ORWHERE(function($where2)use($role1){
                        $where2->where("notice_applications.current_role","<>", $role1)
                        ->where("notice_applications.workflow_id",$this->_GENERAL_NOTICE_WF_MASTER_Id);
                    })
                    ->ORWHERE(function($where2)use($role2){
                        $where2->where("notice_applications.current_role","<>", $role2)
                        ->where("notice_applications.workflow_id",$this->_PAYMENT_NOTICE_WF_MASTER_Id);
                    })
                    ->ORWHERE(function($where2)use($role3){
                        $where2->where("notice_applications.current_role","<>", $role3)
                        ->where("notice_applications.workflow_id",$this->_ILLEGAL_OCCUPATION_WF_MASTER_Id);
                    });
                });
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $application = $application->where(function ($query) use ($key) {
                    $query->orwhere('notice_applications.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.ptn_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("notice_applications.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("notice_applications.firm_name", 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('notice_applications.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $mWardIds = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) 
            {
                $application = $application
                    ->whereBetween(DB::raw('cast(notice_applications.application_date as date)'), [$inputs['formDate'], $inputs['formDate']]);
            } 
            if($request->all)
            {
                $application= $application->get();
                return responseMsg(true, "", $application);
            } 
            $perPage = $request->perPage ? $request->perPage :  10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $paginator = $application->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ]; 
            return responseMsg(true, "", remove_null($list));
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function approveReject(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $request->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            $application = NoticeApplication::where("status",1)->find($request->applicationId);           
            if(!$application)
            {
                throw new Exception("Data Not Found");
            }
            $this->_WF_MASTER_ID = $application->workflow_id;
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id,$ulb_id,$this->_WF_MASTER_ID);
            
            if ($application->current_role != ($role->role_id??0)) 
            {
                return responseMsg(false, "Forbidden Access", "");
            }
            $this->begin();

            // Approval
            if ($request->status == 1) 
            {
                // Objection Application replication
                $application->status=5;
                $id_request = new Request(["ulbId"=>$ulb_id,"paramId"=>$this->_NOTICE_NO_CONST]);
                $id_respons = $this->_ID_GENERATOR->idGenerator($id_request);
                $application->notice_no = $id_respons->original["data"];//$this->generateNoticNo($application->id);
                $application->notice_date = Carbon::now()->format("Y-m-d");
                $application->update();
                $myRquest = new Request(["applicationId"=>$application->id]);
                $this->genrateAndSendNotice($myRquest);
                $msg =  "Notice Successfully Generated !!. Your Notice No. ".$application->notice_no;
                
            }

            // Rejection
            if ($request->status == 0) 
            {
                // Objection Application replication
                $application->status = 4;
                $application->update();
                $msg = "Application Successfully Rejected !!";
                
            }
            $this->commit();
            $data["NoticeNo"] = $application->notice_no;
            return responseMsg(true, $msg, remove_null($data));
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |-------------------------------------------------
     * |  NOT/      |    11222        |  1234          |
     * |    (4)     |   date('dmy')   | uniqueNo       |
     * |________________________________________________
     */
    public function generateNoticNo($applicationId)
    {
        $noticeNO = "NOT/" . date('dmy') . $applicationId;
        return $noticeNO;
    }
    public function applicationStatus($applicationId)
    {
        $refUser        = Auth()->user();
        $refUserId      = $refUser->id ?? 0;
        $refUlbId       = $refUser->ulb_id ?? 0;
        $application = NoticeApplication::find($applicationId);
        $status = "";
        if ($application->status == 5) 
        {
            $status = "Notce Created Successfully";
        } 
        elseif ($application->status == 4) 
        {
            $status = "Notce Rejected";
        } 
        else 
        {
            $rols  = WfRole::find($application->current_role);
            $status = "Notice Pending At " . $rols->role_name;
        } 
        return $status;
    }    

    public function openNoticiList($sedule=false)
    {
        
        try{
            $noticeList = NoticeApplication::select(DB::raw("notice_applications.id"))
                        ->WHERE("notice_applications.is_closed",FALSE)
                        ->WHERE("notice_applications.status",5)
                        ->GET(); 
            
            if($sedule)
            {
                foreach($noticeList as $val)
                {
                    $myRquest = new Request(["applicationId"=>$val["id"]]);
                    $this->genrateAndSendNotice($myRquest);
                }
                return;
            }
            return responseMsg(true, "", remove_null($noticeList));
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), "");
        }
    } 
    public function genrateAndSendNotice(Request $request)
    {
        $user = Auth()->user();
        try{
            
            $noticeData = NoticeApplication::select(
                            "notice_applications.*",
                            "ulb_masters.ulb_name",
                            )
                            ->JOIN("ulb_masters","ulb_masters.id","notice_applications.ulb_id")
                            ->WHERE("notice_applications.id",$request->applicationId)
                            ->WHERE("notice_applications.is_closed",FALSE)
                            ->WHERE("notice_applications.status",5)
                            ->first();
                            
            if(!$noticeData)
            {
                throw new Exception("No Data Found");
            }
            
            $noticeStatus = (!$noticeData->notice_state)? 0 : $noticeData->notice_state;
                
            $sedule = NoticeSedule::where("status",1)
                    ->where("notice_type_id",$noticeData->notice_type_id)
                    ->where("serial_no",($noticeStatus+1))
                    ->where("status",1)
                    ->first(); 
            $totalDays = NoticeSedule::where("status",1)
                        ->where("notice_type_id",$noticeData->notice_type_id)
                        ->where("status",1)
                        ->get();               
            $remider = NoticeReminder::SELECT("*",DB::RAW("CAST(created_at AS DATE) AS created_on"))
                        ->where("status",1)
                        ->where("notice_id",$noticeData->id)
                        ->orderBy("created_at","DESC")
                        ->first();
           
            if($sedule && ($remider ?($remider->reminder_date == Carbon::now()->format('Y-m-d') && $remider->created_on != Carbon::now()->format('Y-m-d')) : true)) 
            { 
                $url = $remider->notice_file??"";
                $temp = explode("/",$url);
                $filename = end($temp);
                if($sedule->serial_no==1 || (!$url) || (!file_exists(storage_path($url)) ))
                {
                    
                    $reminder_notice_date = $sedule->serial_no==1?$noticeData->notice_date:($remider->reminder_date??$noticeData->notice_date);
                    $agency_name = "SRI PUBLICATION & STATIONERS PVT. LTD.";

                    $filename = $noticeData->id."-".$sedule->serial_no."-".time() . '.' . 'pdf';
                    $url = "Uploads/Notice/Remider/".$filename;
                    switch($noticeData->notice_type_id)
                    {
                        case 1 : $pdf = PDF::loadView('general_notice',["noticeData"=>$noticeData,"reminder_notice_date"=>$reminder_notice_date,"agency_name"=>$agency_name]); 
                                break;
                        case 2 : $pdf = PDF::loadView('denial_notice',["noticeData"=>$noticeData,"reminder_notice_date"=>$reminder_notice_date,"agency_name"=>$agency_name]); 
                                break;
                        case 3 : $pdf = PDF::loadView('payment_notice',["noticeData"=>$noticeData,"reminder_notice_date"=>$reminder_notice_date,"agency_name"=>$agency_name]); 
                                break;
                        case 4 : $pdf = PDF::loadView('illegsl_occupation_notice',["noticeData"=>$noticeData,"reminder_notice_date"=>$reminder_notice_date,"agency_name"=>$agency_name]); 
                                break;
                        default : throw new Exception("invalid Notice Type");
                    } 
                    $file = $pdf->download($filename . '.' . 'pdf');
                    $pdf = Storage::put('public' . '/' . $url, $file);

                }
                #=========send Notic==========
                $whatsapp=(Whatsapp_Send($noticeData->mobile_no,"file_test",
                [
                    "conten_type"=>"pdf",
                    [
                        "link"=>config('app.url')."/getImageLink?path=".$url,
                        "filename"=>$this->_NOTICE_CONSTAINT["NOTICE-TYPE-BY-ID"][$noticeData->notice_type_id].".pdf"
                    ]
                ]));

                $whatsapp2=(Whatsapp_Send($noticeData->mobile_no,"trn_2_var",
                ["conten_type"=>"text",
                    [
                        "https://www.smartulb.co.in/RMCDMC/getImageLink.php?path=RANCHI/water_consumer_deactivation/26dd0dbc6e3f4c8043749885523d6a25.pdf",
                        "notice.pdf"
                    ]
                ]));

                // dd($noticeData,$whatsapp??"",$filename??"",$whatsapp2); 
                #=========end send Notice=======
                DB::beginTransaction();
                
                $noticeData->notice_state = $sedule->serial_no;
                $noticeData->update();
                $newRemider = new NoticeReminder();
                $newRemider->notice_id          = $noticeData->id;
                $newRemider->reminder_date      = Carbon::now()->addDays($sedule->priade_in_days)->format('Y-m-d');
                $newRemider->final_date         = $sedule->serial_no==1 ? (Carbon::now()->addDays($totalDays->sum("priade_in_days"))->format('Y-m-d')) : ($remider->final_date??Carbon::now()->addDays($totalDays->sum("priade_in_days"))->format('Y-m-d')) ;
                $newRemider->reminder_content   = $request->reminder_content??null ;
                $newRemider->remarks            = $request->remarks??null;
                $newRemider->user_id            = $user->id??0;
                $newRemider->notice_file        = $url;
                $newRemider->save();
                DB::commit();
            }
            
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getTimelin($id)
    {
        try {
            //    DB::enableQueryLog();
            $time_line =  workflowTrack::select(
                "workflow_tracks.message",
                "workflow_tracks.forward_date",
                "workflow_tracks.forward_time",
                "workflow_tracks.receiver_role_id",
                "role_name",
                DB::raw("workflow_tracks.created_at as receiving_date")
            )
                ->leftjoin('wf_roles', "wf_roles.id", "workflow_tracks.receiver_role_id")
                ->where('workflow_tracks.ref_table_id_value', $id)
                ->where('workflow_tracks.ref_table_dot_id', $this->_REF_TABLE)
                ->whereNotNull('workflow_tracks.sender_role_id')
                ->where('workflow_tracks.status', true)
                ->groupBy(
                    'workflow_tracks.receiver_role_id',
                    'workflow_tracks.message',
                    'workflow_tracks.forward_date',
                    'workflow_tracks.forward_time',
                    'wf_roles.role_name',
                    'workflow_tracks.created_at'
                )
                ->orderBy('workflow_tracks.created_at', 'desc')
                ->get();
            return $time_line;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAllNoticRemider($id)
    {
        try {
            //    DB::enableQueryLog();
            $remider = NoticeReminder::SELECT("*",
                        DB::RAW("CAST(created_at AS DATE) AS created_on")
                        )
                        ->where("status",1)
                        ->where("notice_id",$id)
                        ->orderBy("created_at")                        
                        ->get();
            return $remider;
        } catch (Exception $e) {
            return collect([]);
        }
    }

    public function getDtlByNoticeNo($noticNO,$ulbId="")
    {
        try {
            $noticeData = NoticeApplication::select(
                "notice_applications.*",
                "ulb_masters.ulb_name",
                DB::raw("notice_applications.notice_date AS noticeDate")
                )
            ->JOIN("ulb_masters","ulb_masters.id","notice_applications.ulb_id")
            ->WHERE("notice_applications.notice_no",Str::upper($noticNO))            
            ->WHERE("notice_applications.is_closed",FALSE)
            ->WHERE("notice_applications.status",5);
            
        if($ulbId)
        {
            $noticeData = $noticeData->WHERE("notice_applications.ulb_id",$ulbId);
        }
        $noticeData = $noticeData->first();
        return $noticeData;
        }
        catch (Exception $e) {
            return null;
        } 
    }

    public function noticeClose($id)
    {
        $notic = NoticeApplication::find($id);
        if($notic)
        {
            $notic->is_closed = true;
            $notic->update();
        }
    }

    public function getNoticDtlById($id)
    {
        return NoticeApplication::find($id);
    }
    
 }
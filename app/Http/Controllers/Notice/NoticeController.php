<?php

namespace App\Http\Controllers\Notice;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Property\PropertyDetailsController;
use App\Http\Controllers\Trade\TradeApplication;
use App\Http\Controllers\Water\NewConnectionController;
use App\Http\Requests\Notice\Add;
use App\Models\ModuleMaster;
use App\Models\Notice\NoticeApplication;
use App\Models\Notice\NoticeReminder;
use App\Models\Notice\NoticeSedule;
use App\Models\Notice\NoticeTypeMaster;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\Notice\INotice;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use App\Repository\Trade\ITrade;
use App\Repository\Water\Interfaces\iNewConnection;
use Illuminate\Http\Request;
use App\Traits\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class NoticeController extends Controller
{
    /**
     * Created By Sandeep Bara
     * Date 2023-03-027
     * Notice Module
     */

     use Auth;    
    
    private $_REPOSITORY;
    private $_PROPERTY_REPOSITORY;
    private $_WATER__REPOSITORY;
    private $_TRADE_REPOSITORY;
    private $_COMMON_FUNCTION;
    protected $_GENERAL_NOTICE_WF_MASTER_Id;
    protected $_PAYMENT_NOTICE_WF_MASTER_Id;
    protected $_ILLEGAL_OCCUPATION_WF_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_NOTICE_CONSTAINT;
    protected $_MODULE_CONSTAINT;
    protected $_NOTICE_TYPE;
    public function __construct(INotice $Repository,iPropertyDetailsRepo $propertyRepository,iNewConnection $waterRepository,ITrade $tradeRepository)
    {
        DB::enableQueryLog();
        $this->_REPOSITORY = $Repository;
        $this->_PROPERTY_REPOSITORY = new PropertyDetailsController($propertyRepository);
        $this->_WATER__REPOSITORY = new NewConnectionController($waterRepository);
        $this->_TRADE_REPOSITORY = new TradeApplication($tradeRepository);

        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_GENERAL_NOTICE_WF_MASTER_Id = Config::get('workflow-constants.GENERAL_NOTICE_MASTER_ID');
        $this->_PAYMENT_NOTICE_WF_MASTER_Id = Config::get('workflow-constants.PAYMENT_NOTICE_MASTER_ID');
        $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id = Config::get('workflow-constants.ILLEGAL_OCCUPATION_NOTICE_MASTER_ID');
        $this->_MODULE_ID = Config::get('module-constants.NOTICE_MASTER_ID');
        $this->_MODULE_CONSTAINT = Config::get('module-constants');
        $this->_NOTICE_CONSTAINT = Config::get("NoticeConstaint");
        $this->_REF_TABLE = $this->_NOTICE_CONSTAINT["NOTICE_REF_TABLE"];
        $this->_NOTICE_TYPE = $this->_NOTICE_CONSTAINT["NOTICE-TYPE"]??null;
    }

    public function noticeType(Request $request)
    {
        try{
            $data= DB::connection("pgsql_notice")->table("notice_type_masters")
            ->select("id","notice_type")
                    ->where("status",1)
                    ->get();
            return responseMsg(true, "", $data);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }

    }

    public function serApplication(Request $request)
    {
        
        try{
            $request->validate(
                [
                    "moduleId"=>"required|digits_between:1,6",
                    "value"=>"required",
                    "searchBy"=>"required",
                ]
            );
            $bearerToken = (collect(($request->headers->all())['authorization']??"")->first());
            $contentType = (collect(($request->headers->all())['content-type']??"")->first());
            $data = Http::withHeaders(
                    [
                        "Authorization" => "Bearer $bearerToken",
                        "contentType" => "$contentType",    
                    ]
                );
            $url = null;
            $key = null;
            $moduleId = null;
            $moduleType = null;
            
            if($request->moduleId==1)#property
            {
                $moduleId = $this->_MODULE_CONSTAINT["PROPERTY_MODULE_ID"];
                $moduleType = "PROPERTY";
                if(strtoupper($request->searchBy)=="HOLDING")
                {
                    $key = "holdingNo";
                }
                elseif(strtoupper($request->searchBy)=="MOBILE")
                {
                    $key = "mobileNo";
                }
                elseif(strtoupper($request->searchBy)=="OWNER")
                {
                    $key = "ownerName";
                }
                else{
                    $key = "address";
                }
                $url=("http://192.168.0.165:8008/api/property/get-filter-property-details");
                $request->request->add(["filteredBy"=>"$key","parameter"=>$request->value]);
            }
            if($request->moduleId==2)#water
            {
                $moduleId = $this->_MODULE_CONSTAINT["WATER_MODULE_ID"];
                $moduleType = "WATER CONSUMER";
                if(strtoupper($request->searchBy)=="CONSUMER")
                {
                    $key = "consumerNo";
                }
                elseif(strtoupper($request->searchBy)=="HOLDING")
                {
                    $key = "holdingNo";
                }
                elseif(strtoupper($request->searchBy)=="MOBILE")
                {
                    $key = "mobileNo";
                }
                elseif(strtoupper($request->searchBy)=="OWNER")
                {
                    $key = "applicantName";
                }
                else{
                    $key = "safNo";
                }
                $url=("http://192.168.0.165:8008/api/water/search-consumer");
                $request->request->add(["filterBy"=>"$key","parameter"=>$request->value]);
            }
            if($request->moduleId==3)#trade
            {
                $moduleId = $this->_MODULE_CONSTAINT["TRADE_MODULE_ID"];
                $moduleType = "TRADE LICENSE";
                if(strtoupper($request->searchBy)=="LICENSE")
                {
                    $key = "LICENSE";
                }
                elseif(strtoupper($request->searchBy)=="HOLDING")
                {
                    $key = "HOLDING";
                }
                elseif(strtoupper($request->searchBy)=="MOBILE")
                {
                    $key = "MOBILE";
                }
                elseif(strtoupper($request->searchBy)=="OWNER")
                {
                    $key = "OWNER";
                }
                else{
                    $key = "APPLICATION";
                }
                $url=("http://192.168.0.165:8008/api/trade/application/list");
                $request->request->add(["entityName"=>"$key","entityValue"=>$request->value]);
            }
            
            // if($request->moduleId==4)
            // {
            //     $url=("http://127.0.0.1:8001/api/property/searchByHoldingNo");
            // }
            // if($request->moduleId==5)
            // {
            //     $url=("http://127.0.0.1:8001/api/property/searchByHoldingNo");
            // }
            // if($request->moduleId==6)
            // {
            //     $url=("http://127.0.0.1:8001/api/property/searchByHoldingNo");
            // }
            switch($request->moduleId)
            {
                case 1 : $response =  $this->_PROPERTY_REPOSITORY->propertyListByKey($request);
                         break;
                case 2 : $response =  $this->_WATER__REPOSITORY->searchWaterConsumer($request);
                         break;
                case 3 : $response =  $this->_TRADE_REPOSITORY->readApplication($request);
                         break;
                // case 4 : $response =  $this->_PROPERTY_REPOSITORY->propertyListByKey($request);
                //          break;
                // case 5 : $response =  $this->_PROPERTY_REPOSITORY->propertyListByKey($request);
                //          break;
                // case 6 : $response =  $this->_PROPERTY_REPOSITORY->propertyListByKey($request);
                //          break;
                default: throw new Exception ("Invalid Module");
            }
            // $response =  $data->post($url,$request->all());
            // $responseBody = json_decode($response->getBody());
            // foreach($responseBody->data as $key=>$val)
            // {
            //     $responseBody->data[$key]->moduleId = $moduleId;
            //     $responseBody->data[$key]->moduleType = $moduleType;
            // }
            
            // return($responseBody); 
            
            if(!$response->original["status"])
            {
                throw new Exception($response->original["message"]);
            }
            if($moduleId==3)
            {
                
                $newRespons= ($response->original["data"]["licence"]??$response->original["data"]);
                $response->original["data"] = $newRespons;
                foreach($response->original["data"] as $key=>$val)
                {
                    $response->original["data"][$key]["moduleId"] = $moduleId;
                    $response->original["data"][$key]["moduleType"] = $moduleType;
                    
                }
                
            }
            else{
                foreach($response->original["data"] as $key=>$val)
                {
                    $response->original["data"][$key]["moduleId"] = $moduleId;
                    $response->original["data"][$key]["moduleType"] = $moduleType;
                    
                }
            }

            return responseMsgs($response->original["status"],$response->original["message"],$response->original["data"]);           
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        } 
    }

    public function add(Request $request)
    {
        try {              
            $modul = "SAF,PROPERTY,TRADE LICENSE,WATER CONNECTION,WATER CONSUMER,ADVERTISMENT,MARKET,SOLID WASTE";
            $mRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
            $mFramNameRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\,\-\_\'&\s]+$/';
            $rules = [
                "noticeType" => "required|in:1,2,3,4",
                "moduleName" => "required|regex:/^[a-zA-Z]+$/i",
                "moduleId"      => "nullable|digits_between:1,6",
                "applicationId" => $request->moduleId?"required|digits_between:1,9223372036854775807":"nullable",
                "moduleType"    => $request->moduleId?"required|in:$modul":"nullable|in:$modul",
                "firmName"      => "nullable|regex:$mFramNameRegex",
                "ptnNo"         => "nullable",
                "holdingNo"     =>"nullable",
                "licenseNo"     => "nullable",
                "servedTo"      => "nullable",
                "address"       => "required|regex:$mFramNameRegex",
                "locality"      => "nullable|regex:$mFramNameRegex",
                "mobileNo"      => "required|digits:10|regex:/[0-9]{10}/",
                "noticeDescription" => "required|regex:$mFramNameRegex|min:20",
                "ownerName"     => "nullable|regex:$mRegex",
                "document"     => "required|mimes:pdf,jpg,jpeg,png|max:2048",
            ];
            $request->validate($rules);


            $user = Auth()->user();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $role1 = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_GENERAL_NOTICE_WF_MASTER_Id);
            $role2 = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_PAYMENT_NOTICE_WF_MASTER_Id);
            $role3 = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id);
            
            if (!$role1 && !$role2 && !$role3) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $userType1 = $this->_COMMON_FUNCTION->userType($this->_GENERAL_NOTICE_WF_MASTER_Id);
            $userType2 = $this->_COMMON_FUNCTION->userType($this->_PAYMENT_NOTICE_WF_MASTER_Id);
            $userType3 = $this->_COMMON_FUNCTION->userType($this->_ILLEGAL_OCCUPATION_WF_MASTER_Id);
            // if (!in_array(strtoupper($userType1), ["TC", "UTC"]) && !in_array(strtoupper($userType2), ["TC", "UTC"]) && !in_array(strtoupper($userType3), ["TC", "UTC"])) 
            // {
            //     throw new Exception("You Are Not Authorize For Apply Denial");
            // }            
            return $this->_REPOSITORY->add($request);
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function noticeList(Request $request)
    {
        try{
            $request->validate(
                [
                    "moduleName"=>"required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9\.\,\_\-\']+)*$/",
                    "keyWord"=>"nullable|regex:/^([a-zA-Z])*$/",
                ]
            );
            return $this->_REPOSITORY->noticeList($request);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
        
    }
    public function noticeView(Request $request)
    {
        try{
            $request->validate(
                [
                    "applicationId"=>"required|digits_between:1,9223372036854775807",
                ]
            );
            return $this->_REPOSITORY->noticeView($request);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function fullDtlById(Request $request)
    {
        try{
            $request->validate(
                [
                    "applicationId"=>"required|digits_between:1,9223372036854775807",
                ]
            );
            return $this->_REPOSITORY->fullDtlById($request);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function inbox(Request $request)
    {
        return $this->_REPOSITORY->inbox($request);
    }
    public function outbox(Request $request)
    {
        return $this->_REPOSITORY->outbox($request);
    }
    public function postNextLevel(Request $request)
    {

        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
        
            $request->validate([
                'applicationId' => 'required|integer',
                'senderRoleId' => 'required|integer',
                'receiverRoleId' => 'required|integer',
            ]);
            if(!$this->_COMMON_FUNCTION->checkUsersWithtocken("users"))
            {
                throw New Exception("Citizen Not Allowed");
            }
            
            
            $appllication = NoticeApplication::find($request->applicationId);
            if(!$appllication)
            {
                throw new Exception("Data Not Found");
            }
            
            $refWorkflowId = $appllication->workflow_id;
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            $request->validate([
                'comment' => ($role->is_initiator??false)?"nullable":'required',
            ]);
            // Application Update Current Role Updation
           
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }

            $allRolse = collect($this->_COMMON_FUNCTION->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true));
            $receiverRole = array_values(objToArray($allRolse->where("id",$request->receiverRoleId)))[0]??[];
            $senderRole = array_values(objToArray($allRolse->where("id",$request->senderRoleId)))[0]??[];
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            if($appllication->current_role != $role->role_id)
            {
                throw new Exception("You Have Not Pending This Application");
            }
            $sms ="Application BackWord To ".$receiverRole["role_name"]??"";
            
            if($role->serial_no  < $receiverRole["serial_no"]??0)
            {
                $sms ="Application Forward To ".$receiverRole["role_name"]??"";
            }
           
            DB::beginTransaction();
            $appllication->max_level_attained = ($appllication->max_level_attained < ($receiverRole["serial_no"]??0)) ? ($receiverRole["serial_no"]??0) : $appllication->max_level_attained;
            $appllication->current_role = $request->receiverRoleId;
            $appllication->update();


            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $appllication->workflow_id;
            $metaReqs['refTableDotId'] = $this->_REF_TABLE;
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $metaReqs['user_id']=$user_id;
            $metaReqs['ulb_id']=$ulb_id;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);
            DB::commit();
            return responseMsgs(true, $sms, "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function approveReject(Request $request)
    {
        try{
            $request->validate([
                "applicationId" => "required",
                "status" => "required|in:1,0"
            ]);
            return $this->_REPOSITORY->approveReject($request);
        }catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function openNoticiList($sedule=false)
    {      
        return $this->_REPOSITORY->openNoticiList($sedule);
    }
       
}

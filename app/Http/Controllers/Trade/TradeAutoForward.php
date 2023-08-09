<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\ActiveTradeOwner;
use App\Models\UlbMaster;
use App\Models\Workflows\WfActiveDocument;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITrade;
use App\Repository\Trade\Trade;
use App\Traits\Trade\TradeTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TradeAutoForward extends Controller
{
    use TradeTrait;
    //
    protected $_MODLE_ACTIVE_LICESENS;
    protected $_MODLE_WF_TRACK;
    protected $_MODLE_ACTIVE_DOC;
    protected $_MODLE_ACTIVE_OWNERS;
    protected $_MODLE_ULB_MSTR;

    protected $_TODAYS;
    protected $_COMMON_FUNCTION;
    protected $_TradeApplication_controler;
    protected $_TRADE_C;
    protected $_WF_MASTER_Id;
    protected $_WF_NOTICE_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;

    #assistent Variable
    protected $_PERMITED_ULB;
    protected $_PERMITED_ULB_ID;
    protected $_ID;
    protected $_ROLE;
    protected $_USER_ID;
    protected $_ROLE_ID;
    protected $_RECIVER_ROLE_ID;
    protected $_SENDER_ROLE_ID;
    protected $_ULB_ID;
    protected $_ALL_ROLSE;

    public function __construct()
    {
        DB::enableQueryLog();
        #class declaration
        $this->_TradeApplication_controler = App::makeWith(TradeApplication::class,["ITrade"=>app(ITrade::class)]);
        $this->_COMMON_FUNCTION         = new CommonFunction();
        $this->_TRADE_C                 = new Trade();
        #constaint declaration
        $this->_WF_MASTER_Id            = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id     = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID               = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT         = Config::get("TradeConstant");
        $this->_REF_TABLE               = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
        $this->_PERMITED_ULB            = Config::get("AutoForwardConstant");
        $this->_PERMITED_ULB_ID         = (collect($this->_PERMITED_ULB)
                                            ->where("status",1)
                                            ->where("module_id",$this->_MODULE_ID)
                                            ->where("work_mstr_id",$this->_WF_MASTER_Id)
                                            )
                                            ->pluck("ulb_id");
        $this->_TODAYS                  = Carbon::now();

        #modle declaration
        $this->_MODLE_ACTIVE_LICESENS   = new ActiveTradeLicence();
        $this->_MODLE_ACTIVE_OWNERS     = new ActiveTradeOwner();
        $this->_MODLE_WF_TRACK          = new WorkflowTrack();
        $this->_MODLE_ACTIVE_DOC        = new WfActiveDocument();
        $this->_MODLE_ULB_MSTR          = new UlbMaster();
    }
    /**
     * AutoForwad Code By System
     */
    #==========================statrd======================
    public function AutoForwardAssistent()
    {
        $roles = $this->_COMMON_FUNCTION->getWorkFlowAllRoles(0,2,$this->_WF_MASTER_Id,1);
        $roles =$this->_COMMON_FUNCTION->sortsWorkflowRols($roles);
        $data = collect($roles)->map(function($val){
             return $this->assistentTask($val);
        });
        return $data;
    }
    private function assistentTask(array $roleDtl)
    {
        try{            
            $fromDay = $this->_TODAYS->subDays(3)->format("Y-m-d");
            $forwardData = DB::table("active_trade_licences")
            ->select(
                DB::raw("CAST(workflow_tracks.track_date AS  DATE),
                        workflow_tracks.id as workflow_track_id,
                        test.max_id,
                        workflow_tracks.ref_table_id_value,
                        active_trade_licences.id,
                        active_trade_licences.ulb_id,
                        active_trade_licences.current_role, 
                        workflow_tracks.receiver_role_id,
                        application_no")
            )
            ->join("workflow_tracks",function($join){
                    $join->on("workflow_tracks.ref_table_id_value","active_trade_licences.id")
                    ->where(DB::raw("workflow_tracks.receiver_role_id") ,"=",DB::raw("active_trade_licences.current_role"));
                })
            ->join(DB::raw("
                            (
                                select max(workflow_tracks.id) max_id, workflow_tracks.ref_table_id_value 
                                from workflow_tracks
                                join active_trade_licences on active_trade_licences.id = workflow_tracks.ref_table_id_value  
                                    and workflow_tracks.ref_table_dot_id = 'active_trade_licences'
                                    and workflow_tracks.ulb_id = active_trade_licences.ulb_id
                                where workflow_tracks.status = true   
                                    and workflow_tracks.ulb_id = 2
                                    and workflow_tracks.receiver_role_id is not null
                                    and workflow_tracks.sender_role_id is not null
                                group by workflow_tracks.ref_table_id_value
                            ) test
                "),function($join){
                    $join->on("test.max_id","workflow_tracks.id");
            })
            ->whereIn("active_trade_licences.ulb_id",$this->_PERMITED_ULB_ID)
            ->where("active_trade_licences.is_parked",false)
            ->where("active_trade_licences.is_active",true)
            ->where("active_trade_licences.pending_status",1)
            ->where("active_trade_licences.payment_status",1)
            ->where("active_trade_licences.document_upload_status",1)
            ->where("active_trade_licences.current_role",$roleDtl["id"]) 
            ->where(DB::raw("CAST(workflow_tracks.track_date AS  DATE)"),"<=",$fromDay)
            ->get();
            $forwardData->map(function($val) use($roleDtl){

                $this->_ID              = $val->id;
                $this->_USER_ID         =0;
                $this->_ULB_ID          = $val->ulb_id;
                $this->_SENDER_ROLE_ID  = $val->current_role;
                $this->_RECIVER_ROLE_ID = $roleDtl["forward_role_id"];
                $this->_ALL_ROLSE     = collect($this->_COMMON_FUNCTION->getAllRoles($this->_USER_ID, $this->_ULB_ID, $this->_WF_MASTER_Id, 0, true));
                
                switch($val->current_role)
                {
                    case 11: print_var("BO Login");                             
                             $this->assistentForward();
                            break;
                    case 6: print_var("DA Login");
                             $this->assistentForward();
                            break;
                    case 15: print_var("TD Login");
                             $this->assistentForward();
                            break;
                    case 13: print_var("SH Login");
                             $this->assistentForward();
                            break;
                    case 10: print_var("EO Login");
                             $this->assistentForward();
                            break;
                }
            });
            if(!$forwardData->isEmpty())
            return $forwardData;
        }
        catch(Exception $e)
        {    
            return false;
        }
    }

    private function assistentForward()
    {
        try{                    
            $allRolse     = $this->_ALL_ROLSE;
            $receiverRole = array_values(objToArray($allRolse->where("id", $this->_RECIVER_ROLE_ID)))[0] ?? [];
            $senderRole   = array_values(objToArray($allRolse->where("id", $this->_SENDER_ROLE_ID)))[0] ?? [];
            
            $licence = $this->_MODLE_ACTIVE_LICESENS::find($this->_ID);
            $track   = $this->_MODLE_WF_TRACK;
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $this->_ID)
                ->where('module_id', $this->_MODULE_ID)
                ->where('ref_table_dot_id', $this->_REF_TABLE)
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();
            $licence->max_level_attained = ($licence->max_level_attained < ($receiverRole["serial_no"] ?? 0)) ? ($receiverRole["serial_no"] ?? 0) : $licence->max_level_attained;
            $licence->current_role = $this->_RECIVER_ROLE_ID ? $this->_RECIVER_ROLE_ID :$licence->current_role;
            if ($licence->is_parked) 
            {
                $licence->is_parked = false;
            }
            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $licence->workflow_id;
            $metaReqs['refTableDotId'] = $this->_REF_TABLE;
            $metaReqs['refTableIdValue'] = $this->_ID;
            $metaReqs['user_id'] = $this->_USER_ID;
            $metaReqs['ulb_id'] = $this->_ULB_ID;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = $this->_TODAYS->format('Y-m-d');
            $metaReqs['forwardTime'] = $this->_TODAYS->format('H:i:s');
            $metaReqs['verificationStatus'] = 1;
            $metaReqs['comment'] = ($senderRole["is_finisher"]??false)?"Auto Aproved":"Auto Forward";
            $metaReqs['senderRoleId'] = $this->_SENDER_ROLE_ID;
            $metaReqs['receiverRoleId'] = $this->_RECIVER_ROLE_ID;
            $request = new Request($metaReqs);

            if($senderRole["can_verify_document"])         
            DB::beginTransaction();
            $licence->update();   
            if(($senderRole["can_verify_document"]??false) && !$this->assistentDocVeriFy())
            {
                throw new Exception();
            }
            if(($senderRole["is_finisher"]??false) && !$this->assistentAppAroved($licence))
            {
                throw new Exception();
            }                 
            $track->saveTrack($request);
            DB::commit();

            return true;
        } 
        catch(Exception $e)
        {
            DB::rollBack();
            return false;
        } 
    }

    private function assistentDocVeriFy()
    {
        try{
            $request = new Request(["applicationId"=>$this->_ID]);
            $doc = $this->_TradeApplication_controler->getUploadDocuments($request);       
            if($doc->original["status"]??false)
            {
                $myRequest = [
                    'remarks' => "Auto Verify",
                    'verify_status' => 1,
                    'action_taken_by' => $this->_USER_ID,
                ];
                $mWfDocument = $this->_MODLE_ACTIVE_DOC;
                DB::beginTransaction();
                $doc->original["data"]->map(function($val)use($mWfDocument,$myRequest){
                    $mWfDocument->docVerifyReject($val["id"], $myRequest);
                    
                });
                DB::commit();
            }
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }

    private function assistentAppAroved(ActiveTradeLicence $activeLicence)
    {
        try{
            $refUlbDtl          = $this->_MODLE_ULB_MSTR::find($activeLicence->ulb_id);
            # Objection Application replication
            $approvedLicence = $activeLicence->replicate();
            $approvedLicence->setTable('trade_licences');
            $approvedLicence->pending_status = 5;
            $approvedLicence->id = $activeLicence->id;
            $status = $this->giveValidity($approvedLicence);
            if (!$status) {
                throw new Exception("Some Error Occurs");
            }
            $approvedLicence->save();
            $owneres = $this->_MODLE_ACTIVE_OWNERS::select("*")
                ->where("temp_id", $activeLicence->id)
                ->get();
            foreach ($owneres as $val) 
            {
                $refOwners = $val->replicate();
                $refOwners->id = $val->id;
                $refOwners->setTable('trade_owners');
                $refOwners->save();
                $val->delete();
            }
            $activeLicence->delete();
            $licenseNo = $approvedLicence->license_no;
            $sms = trade(["application_no" => $approvedLicence->application_no, "licence_no" => $approvedLicence->license_no, "ulb_name" => $refUlbDtl->ulb_name ?? ""], "Application Approved");
            if($sms["status"]??false)
            {
                foreach ($owneres as $val) 
                {
                    # NOTIFICATION SERVICES
                    $smsLog = send_sms($val->mobile_no,$sms["sms"],$sms["temp_id"]);
                }
            }
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
    }
    #======================end hear=========================================
}

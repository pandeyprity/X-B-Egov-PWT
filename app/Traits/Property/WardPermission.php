<?php

namespace App\Traits\Property;

use App\Models\User;
use App\Models\Ward\WardUser;
use App\Models\WorkflowCandidate;
use App\Models\Workflows\UlbWorkflowRole;
use App\Repository\UlbWorkflow\UlbWorkflow;
use App\Traits\Auth;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Trait for get ward permission of current user and other Common Data for Saf workflow and Objection workFlow also
 * Created for redusing query exicution and storing data in redis codes
 * --------------------------------------------------------------------------------------------------------
 * Created by-Sandeep Bara
 * Created On-28-08-2022 
 * --------------------------------------------------------------------------------------------------------
 */
trait WardPermission
{
    use Auth;
    public function work_flow_candidate($user_id, $ulb_id)
    {
        $redis = Redis::connection();
        $work_flow_candidate = json_decode(Redis::get('workflow_candidate:' . $user_id), true) ?? null;
        if (!$work_flow_candidate) {
            $work_flow_candidate = WorkflowCandidate::select('workflow_candidates.id', "ulb_workflow_masters.module_id")
                ->join('ulb_workflow_masters', 'ulb_workflow_masters.id', 'workflow_candidates.ulb_workflow_id')
                ->where('workflow_candidates.user_id', $user_id)
                ->where('ulb_workflow_masters.ulb_id', $ulb_id)
                ->first();
            if (!$work_flow_candidate) {
                $message = ["status" => false, "data" => [], "message" => "Your Are Not Authoried"];
                return response()->json($message, 200);
            }
            $this->WorkflowCandidateSet($redis, $user_id, $work_flow_candidate);
        }
        return $work_flow_candidate;
    }
    public function WardPermission($user_id)
    {
        $redis = Redis::connection();
        $ward_permission = json_decode(Redis::get('WardPermission:' . $user_id), true) ?? null;
        if (!$ward_permission) {
            Redis::del('WardPermission:' . $user_id);
            $ward_permission = WardUser::select("ulb_ward_id")
                ->where('user_id', $user_id)
                ->orderBy('ulb_ward_id')
                ->get();
            $ward_permission = adjToArray($ward_permission);
            $this->WardPermissionSet($redis, $user_id, $ward_permission);
        }
        return $ward_permission;
    }
    public function getWorkFlowRoles($user_id, int $ulb_id, int $work_flow_id)
    {
        $redis = Redis::connection();
        $workflow_rolse = json_decode(Redis::get('WorkFlowRoles:' . $user_id . ":" . $work_flow_id), true) ?? null;
        if (!$workflow_rolse) {
            $workflow_rolse = UlbWorkflowRole::select(
                DB::raw("workflows.id as workflow_id"),
                "role_masters.id",
                "role_masters.role_name",
                "ulb_workflow_roles.forward_id",
                "ulb_workflow_roles.backward_id",
                "ulb_workflow_roles.show_full_list",
                "ulb_workflow_masters.ulb_id",
                "module_masters.module_name",
                "workflows.workflow_name"
            )
                ->join("role_masters", "role_masters.id", "ulb_workflow_roles.role_id")
                ->join("ulb_workflow_masters", function ($join) use ($ulb_id) {
                    $join->on("ulb_workflow_masters.id", "ulb_workflow_roles.ulb_workflow_id")
                        ->where("ulb_workflow_masters.ulb_id", $ulb_id);
                })
                ->join("module_masters", "module_masters.id", "ulb_workflow_masters.module_id")
                ->join("workflows", function ($join) use ($work_flow_id) {
                    $join->on("workflows.module_id", "module_masters.id")
                        ->where("workflows.id", $work_flow_id);
                })
                ->get();
            $workflow_rolse = adjToArray($workflow_rolse);
            $this->WorkFlowRolesSet($redis, $user_id, $workflow_rolse, $work_flow_id);
        }
        return $workflow_rolse;
    }

    public function getForwordBackwordRoll($user_id, int $ulb_id, int $work_flow_id, int $role_id, $finisher = null)
    {
        $retuns = [];
        $workflow_rolse = $this->getWorkFlowRoles($user_id, $ulb_id, $work_flow_id);
        $backwordForword = array_filter($workflow_rolse, function ($val) use ($role_id) {
            return $val['id'] == $role_id;
        });
        $backwordForword = array_values($backwordForword)[0] ?? [];
        if ($backwordForword) {
            $data = array_map(function ($val) use ($backwordForword) {
                if ($val['id'] == $backwordForword['forward_id']) {
                    return ['forward' => ['id' => $val['id'], 'role_name' => $val['role_name']]];
                }
                if ($val['id'] == $backwordForword['backward_id']) {
                    return ['backward' => ['id' => $val['id'], 'role_name' => $val['role_name']]];
                }
            }, $workflow_rolse);
            $data = array_filter($data, function ($val) {
                return is_array($val);
            });
            $data = array_values($data);

            $forward = array_map(function ($val) {
                return $val['forward'] ?? false;
            }, $data);

            $forward = array_filter($forward, function ($val) {
                return is_array($val);
            });
            $forward = array_values($forward)[0] ?? [];

            $backward = array_map(function ($val) {
                return $val['backward'] ?? false;
            }, $data);

            $backward = array_filter($backward, function ($val) {
                return is_array($val);
            });
            $backward = array_values($backward)[0] ?? [];
            // dd($backward);
            $retuns["backward"] = $backward;
            $retuns["forward"] = $forward;
        }
        return $retuns;
    }

    public function getAllRoles($user_id, int $ulb_id, int $work_flow_id, int $role_id)
    {
        try {
            $data = $this->getWorkFlowRoles($user_id, $ulb_id, $work_flow_id, $role_id);
            $curentUser = array_filter($data, function ($val) use ($role_id) {
                return $val['id'] == $role_id;
            });
            $curentUser = array_values($curentUser)[0];
            $data = array_filter($data, function ($val) use ($curentUser) {
                return (!in_array($val['id'], [$curentUser['forward_id'], $curentUser['backward_id']]));
            });
            return ($data);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    public function getUserRoll($user_id, $worklow_name = 'SAF', $module_name = 'Property')
    {
        $user = User::select(
            DB::raw("role_masters.id as role_id, 
                                    workflows.id as workflow_id,role_users.id as dd"),
            "users.id",
            "role_masters.role_name"
        )
            ->join('role_users', 'role_users.user_id', 'users.id')
            ->join('role_masters', 'role_masters.id', 'role_users.role_id')
            ->join('workflows', 'workflows.id', 'role_users.workflow_id')
            ->join('module_masters', 'module_masters.id', 'workflows.module_id')
            ->where('users.id', $user_id)
            ->where('workflows.workflow_name', $worklow_name)
            ->where('module_masters.module_name', $module_name)
            ->orderBy('role_users.id', 'desc')
            ->first();

        // dd($user);
        return $user;
    }
}

<?php

namespace App\Traits;

use App\Models\Markets\MarketPriceMstrs;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/**
 * | Workflow Masters Trait
 */

trait WorkflowTrait
{
    /**
     * | Get Ulb Workflow Id By Ulb Id
     * | @param Bearer bearer token from request
     * | @param ulbId 
     * | @param workflowId 
     */
    public function getUlbWorkflowId($bearer, $ulbId, $wfMasterId)
    {
        $baseUrl = Config::get('constants.AUTH_URL');
        $workflows = Http::withHeaders([
            "Authorization" => "Bearer $bearer",
            "contentType" => "application/json"

        ])->post($baseUrl . 'api/workflow/get-ulb-workflow', [
            "ulbId" => $ulbId,
            "workflowMstrId" => $wfMasterId
        ])->json();
        // $promise = Http::async()->post($baseUrl . 'api/workflow/get-ulb-workflow')->then(function ($response) {
        //     echo $response; 
        // });
        // print_r($promise);
        return $workflows;
    }

    /**
     * | Get Roles by Logged In user Id
     * | @param userId Logged In UserId
     */
    public function getRoleByUserId($bearer)
    {
        $baseUrl = Config::get('constants.AUTH_URL');
        $roles = Http::withHeaders([
            "Authorization" => "Bearer $bearer",
            "contentType" => "application/json"
        ])->post($baseUrl . 'api/role-by-user')->json();
        return $roles['data'];
    }

       /**
     * | Get Finisher Id while approve or reject application
     * | @param wfWorkflowId ulb workflow id 
     */
    public function getFinisherId(int $wfWorkflowId)
    {
        $query = "SELECT 
                    r.id AS role_id,
                    r.role_name AS role_name 
                    FROM wf_roles r
                    INNER JOIN (SELECT * FROM wf_workflowrolemaps WHERE workflow_id=$wfWorkflowId) w ON w.wf_role_id=r.id
                    WHERE w.is_finisher=TRUE ";
        return $query;
    }

     /**
     * | get Ward By Logged in User Id
     * -------------------------------------------
     * | @param userId > Current Logged In User Id
     */
    public function getWardByUserId($userId)
    {
        $occupiedWard = WfWardUser::select('id', 'ward_id')
            ->where('user_id', $userId)
            ->get();
        return $occupiedWard;
    }

         /**
     * | get workflow role Id by logged in User Id
     * -------------------------------------------
     * @param userId > current Logged in User
     */
    public function getRoleIdByUserId($userId)
    {
        $roles = WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
        return $roles;
    }
}

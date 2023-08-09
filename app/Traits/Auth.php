<?php

namespace App\Traits;

use App\Models\Menu\WfRolemenu;
use App\Models\User;
use App\Models\Workflows\WfRoleusermap;
use App\Repository\Menu\Concrete\MenuRepo;
use Illuminate\Http\Request;
use App\MicroServices\DocUpload;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Razorpay\Api\Collection;

/**
 * Trait for saving and editing the Users and Citizen register also
 * Created for reducing the duplication for the Saving and editing codes
 * --------------------------------------------------------------------------------------------------------
 * Created by-Anshu Kumar
 * Updated by-Sam kerketta
 * Created On-16-07-2022 
 * --------------------------------------------------------------------------------------------------------
 */

trait Auth
{
    /**
     * Saving User Credentials 
     */
    public function saving($user, $request)
    {
        $docUpload = new DocUpload;
        $imageRelativePath = 'Uploads/User/Photo';
        $signatureRelativePath = 'Uploads/User/Signature';
        $user->name = $request->name;
        $user->mobile = $request->mobile;
        $user->email = $request->email;
        // $user->ulb_id = $request->ulb;
        if ($request->ulb) {
            $user->ulb_id = $request->ulb;
        }
        if ($request->userType) {
            $user->user_type = $request->userType;
        }
        if ($request->description) {
            $user->description = $request->description;
        }
        if ($request->workflowParticipant) {
            $user->workflow_participant = $request->workflowParticipant;
        }
        if ($request->photo) {
            $filename = explode('.', $request->photo->getClientOriginalName());
            $document = $request->photo;
            $imageName = $docUpload->upload($filename[0], $document, $imageRelativePath);
            $user->photo_relative_path = $imageRelativePath;
            $user->photo = $imageName;
        }
        if ($request->signature) {
            $filename = explode('.', $request->signature->getClientOriginalName());
            $document = $request->signature;
            $imageName = $docUpload->upload($filename[0], $document, $signatureRelativePath);
            $user->sign_relative_path = $signatureRelativePath;
            $user->signature = $imageName;
        }

        $token = Str::random(80);                       //Generating Random Token for Initial
        $user->remember_token = $token;
    }

    /**
     * Saving Extra User Credentials
     */
    public function savingExtras($user, $request)
    {
        if ($request->suspended) {
            $user->suspended = $request->suspended;
        }
        if ($request->superUser) {
            $user->super_user = $request->superUser;
        }
    }

    /**
     * Save User Credentials On Redis 
     */
    public function redisStore($redis, $emailInfo, $request, $token)
    {
        $redis->set(
            'user:' . $emailInfo->id,
            json_encode([
                'id' => $emailInfo->id,
                'name' => $emailInfo->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'remember_token' => $token,
                'mobile' => $emailInfo->mobile,
                'user_type' => $emailInfo->user_type,
                'ulb_id' => $emailInfo->ulb_id,
                'created_at' => $emailInfo->created_at,
                'updated_at' => $emailInfo->updated_at
            ])
        );
    }

    /**
     * | response Messages for Success Login
     * | @param BearerToken $token
     * | @return Response
     */
    public function tResponseSuccess($token, $emailInfo, $request)
    {
        $userDetails = $this->getUserDetails($emailInfo, $request);                           //<------------ calling user details
        $response = ['status' => true, 'message' => 'You Have Logged In!!', 'data' => ["token" => $token, 'userDetails' => $userDetails]];
        return $response;
    }

    /**
     * | response messages for failure login
     * | @param Msg The conditional messages 
     */
    public function tResponseFail($msg)
    {
        $response = ['status' => false, 'data' => '', 'message' => $msg];
        return $response;
    }

    /**     
     * Save put Workflow_candidate On User Credentials On Redis 
     */

    public function WorkflowCandidateSet($redis, $user_id, $Workflow_candidate)
    {
        $redis->set(
            'workflow_candidate:' . $user_id,
            json_encode([
                'id' => $Workflow_candidate->id,
                'module_id' => $Workflow_candidate->module_id,
            ])
        );
        $redis->expire('workflow_candidate:' . $user_id, 18000);
    }

    public function WardPermissionSet($redis, $user_id, array $Workflow_candidate)
    {
        $redis->set(
            'WardPermission:' . $user_id,
            json_encode($Workflow_candidate)
        );
        $redis->expire('WardPermission:' . $user_id, 18000);
    }

    public function WorkFlowRolesSet($redis, $user_id, array $workflow_rolse, $work_flow_id)
    {
        $redis->set(
            'WorkFlowRoles:' . $user_id . ":" . $work_flow_id,
            json_encode($workflow_rolse)
        );
        $redis->expire('WorkFlowRoles:' . $user_id . ":" . $work_flow_id, 18000);
    }
    public function AllVacantLandRentalRateSet($redis, array $rentalVal)
    {
        $redis->set(
            'AllVacantLandRentalRate',
            json_encode($rentalVal)
        );
        $redis->expire('AllVacantLandRentalRate', 18000);
    }
    public function AllRentalValueSet($redis, int $ulb_id, array $rentalVal)
    {
        $redis->set(
            "AllRentalValue:$ulb_id",
            json_encode($rentalVal)
        );
        $redis->expire("AllRentalValue:$ulb_id", 18000);
    }
    public function AllBuildingUsageFacterSet($redis, array $rentalVal)
    {
        $redis->set(
            "AllBuildingUsageFacter",
            json_encode($rentalVal)
        );
        $redis->expire("AllBuildingUsageFacter", 18000);
    }
    public function AllBuildingRentalValueSet($redis, int $ulb_id, array $rentalVal)
    {
        $redis->set(
            "AllBuildingRentalValue:$ulb_id",
            json_encode($rentalVal)
        );
        $redis->expire("AllBuildingRentalValue:$ulb_id", 18000);
    }
    public function OccuPencyFacterSet($redis, array $OccuPencyFacter)
    {
        $redis->set(
            "OccuPencyFacter",
            json_encode($OccuPencyFacter)
        );
        $redis->expire("OccuPencyFacter", 18000);
    }
    public function AllCircleRateSet($redis, int $ulb_id, array $OccuPencyFacter)
    {
        $redis->set(
            "AllCircleRate:$ulb_id",
            json_encode($OccuPencyFacter)
        );
        $redis->expire("AllCircleRate:$ulb_id", 18000);
    }

    /**
     * | query for save ulb and role on user login
     */
    public function query($id)
    {
        $query = "SELECT 
        u.id,
        u.name AS NAME,
        u.user_name AS USER_NAME,
        u.mobile AS mobile,
        u.email AS email,
        u.ulb_id,
        um.ulb_name
            FROM users u 
            
            LEFT JOIN ulb_masters um ON um.id=u.ulb_id
            WHERE u.id=$id";
        return $query;
    }

    /**
     * |------------------------ Get User Details According to token / user ---------------------------
     * |@param emailInfo
     * |@var citizen : static variable 
     * |@var userInfo
     * |@var userId
     * |@var menuDetails
     * |@var menuRoleDetails
     * |@var roleId
     * |@var roleBasedMenu
     * |@var collection
     * |@return collection[] : returning the user details 
         |line-(248,253) Working
     * | Remark : use collect in place of foreach.
     */
    public function getUserDetails($emailInfo, $request)
    {
        $userInfo = User::where('email', $emailInfo)
            ->select(
                'id',
                'name AS name',
                'user_type AS userType',
                'ulb_id as ulbId'
            )
            ->first();

        $collection['userName'] = $userInfo->name ?? null;
        $collection['userType'] = $userInfo->userType;
        $collection['ulbId'] = $userInfo->ulbId;
        $userId = $userInfo->id;

        # collecting the roles for respective user
        $mWfRoleusermap = new WfRoleusermap();
        $menuRoleDetails = $mWfRoleusermap->getRoleDetailsByUserId($userId);

        if (empty(collect($menuRoleDetails)->first())) {
            return ("User has No Roles!");
        }

        $collection['role'] = collect($menuRoleDetails)->map(function ($value, $key) {
            $values = $value['roles'];
            return $values;
        });

        $this->checkForMobileView($menuRoleDetails, $request);
        //-->>
        return $collection;
    }


    /**
     * | Check for the mobile View
     * | @param 
     * | @var
     * | @return
     */
    public function checkForMobileView($menuRoleDetails, $request)
    {
        $roleIds = collect($menuRoleDetails)->pluck("roleId")->toArray();
        $refRoleIds = Config::get("workflow-constants.ROLES");
        switch ($request->type) {
            case ("mobile"):
                if (in_array($refRoleIds['ULB_Tax_Collector'], $roleIds) || in_array($refRoleIds['Tax_Collector'], $roleIds) || in_array($refRoleIds['Team_Leader'], $roleIds)) {
                    true;
                } else {
                    throw new Exception("You are not authorised for mobile View!");
                }
                break;
            case (""):
                if (in_array($refRoleIds['ULB_Tax_Collector'], $roleIds) || in_array($refRoleIds['Tax_Collector'], $roleIds)) {
                    throw new Exception("You are not authorised for web View!");
                } else {
                    true;
                }
                break;
        }
    }
}


        // -->>
        // $roleId = $menuRoleDetails['roleId'] = collect($menuRoleDetails)->map(function ($value, $key) {
        //     $values = $value['roleId'];
        //     return $values;
        // });

        // # representing the menu details for each role
        // foreach ($roleId as $roleIds) {
        //     $mWfRolemenu = new WfRolemenu();
        //     $roleBasedMenu[] = $mWfRolemenu->getMenuDetailsByRoleId($roleIds);
        // }

        // $menuDetails = collect($roleBasedMenu)->collapse();
        // $collection['menuPermission'] = $menuDetails->unique()->values();

        # calling the menu permission
        // $metaReqs = [
        //     'roleId' => $menuRoleDetails->pluck('roleId'),
        // ];
        // $lodeData = new Request($metaReqs);
        // $mMenuRepo = new MenuRepo();
        // $treeStructure = $mMenuRepo->generateMenuTree($lodeData);
        // $collection['menuPermission'] = collect($treeStructure)['original']['data'];
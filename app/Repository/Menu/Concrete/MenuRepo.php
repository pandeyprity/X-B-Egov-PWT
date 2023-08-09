<?php

namespace App\Repository\Menu\Concrete;

use App\Models\Menu\MenuMaster;
use App\Models\Menu\WfRolemenu;
use App\Repository\Menu\Interface\iMenuRepo;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-23-11-2022 
 * | Created By-Anshu Kumar
 * | Updated On-25-11-2022
 * | Updated By-Sam Kerketta
 * | Repository for the Menu Permission
 */

class MenuRepo implements iMenuRepo
{
    private $_redis;
    public function __construct()
    {
        $this->_redis = Redis::connection();
    }

    /**
     * |-------------------------------------------- Get All the Menu By Roles ---------------------------------------------------------|
     * | @param req
     * | @var query : raw querry for the role wise menu
     * | @var menues
     * | @return menues
     * | Query Time - 343ms 
     * | rating-2
        | Serial No : 01
        | Closed
     */
    public function getMenuByRoles($req)
    {
        $mQuery = "SELECT 
                            m.id AS menu_id,
                            m.serial,
                            m.description, 
                            m.menu_string,
                            m.parent_id,
                            r.role_id,
                            (CASE 
                                WHEN r.role_id IS NOT NULL THEN TRUE 
                                ELSE 
                                FALSE
                            END) AS permission_status
                            FROM menu_masters AS m
                    LEFT JOIN (SELECT * FROM wf_rolemenus WHERE role_id=$req->roleId AND status=1) AS r ON r.menu_id=m.id
                    WHERE m.parent_id > '0'";
        $menues = DB::select($mQuery);
        $this->_redis->set('menu-by-role-' . $req->roleId, json_encode($menues));               // Caching the data should be flush while adding new menu to the role

        return responseMsg(true, "Permission Menues", remove_null($menues));
    }


    /**
     * |------------------------------------------ update role menues ------------------------------------------------------|
     * | @param req
     * | @var roleMenus / Obj
     * | @var readRoleMenus
     * | Query Time - 366 ms 
     * | Status-Closed 
     * | Rating-2
        |  Serial No : 02
        | Closed
     */
    public function updateMenuByRole($req)
    {
        $mRoleMenus = new WfRolemenu();                                                          // Flush Key of the User Role Permission
        $mReadRoleMenus = $mRoleMenus->getMenues($req);

        if ($mReadRoleMenus) {                                                                   // If Data Already Existing
            switch ($req->status) {
                case 1;
                    $mReadRoleMenus->status = 1;
                    $mReadRoleMenus->save();
                    return responseMsg(true, "Successfully Enabled the Menu Permission for the Role", "");
                    break;
                case 0;
                    $mReadRoleMenus->status = 0;
                    $mReadRoleMenus->save();
                    return responseMsg(true, "Successfully Disabled the Menu Permission for the Role", "");
                    break;
            }
        }
        $mRoleMenus->role_id        = $req->roleId;
        $mRoleMenus->menu_id        = $req->menuId;
        $mRoleMenus->route          = $req->route ?? null;
        $mRoleMenus->save();
        return responseMsg(true, "Successfully Enabled the Menu Permission for the Role", "");
    }

    /**
     * |---------------------- Algorithem for the generation of the menu  paren/childeran structure -------------------|
     * | @param req
     * | @var menuMaster / Obj
     * | @var menues
     * | @var data
     * | @var itemsByReference
     * | @var item
     * | Query Time = 308ms 
     * | Rating- 4
     * | Status- Working
        | Serial No : 03
        | Closed
     */
    public function generateMenuTree($req)
    {
        $mMenuMaster = new MenuMaster();
        $mMenues = $mMenuMaster->fetchAllMenues();

        $data = collect($mMenues)->map(function ($value, $key) {
            $return = array();
            $return['id'] = $value['id'];
            $return['parentId'] = $value['parent_id'];
            $return['path'] = $value['route'];
            $return['icon'] = config('app.url') . '/api/getImageLink?path=' . $value['icon'];
            $return['name'] = $value['menu_string'];
            $return['order'] = $value['serial'];
            $return['children'] = array();
            return ($return);
        });

        $data = (objToArray($data));
        $itemsByReference = array();

        foreach ($data as $key => &$item) {
            $itemsByReference[$item['id']] = &$item;
        }

        # looping for the generation of child nodes / operation will end if the parentId is not match to id 
        foreach ($data as $key => &$item)
            if ($item['id'] && isset($itemsByReference[$item['parentId']]))
                $itemsByReference[$item['parentId']]['children'][] = &$item;

        # this loop is to remove the external loop of the child node ie. not allowing the child node to create its own treee
        foreach ($data as $key => &$item) {
            if ($item['parentId'] && isset($itemsByReference[$item['parentId']]))
                unset($data[$key]);
        }

        $data = collect($data)->values();
        if ($req->roleId && $req->moduleId) {
            $mRoleMenues = $mMenuMaster->getMenuByRole($req->roleId, $req->moduleId); //addition of module Id

            $roleWise = collect($mRoleMenues)->map(function ($value) use ($mMenuMaster) {
                if ($value['parent_id'] > 0) {
                    return $roleWise = $this->getParent($value['parent_id']);
                }
                return $value['id'];
            });
            $retunProperValues = collect($data)->map(function ($value, $key) use ($roleWise) {
                if ($roleWise->contains($value['id'])) {
                    return $value;
                }
            });
            return responseMsgs(true, "OPERATION OK!", $retunProperValues->filter()->values(), "", "01", "308.ms", "POST", $req->deviceId);
        }
        return responseMsgs(true, "OPERATION OK!", $data, "", "01", "308.ms", "POST", $req->deviceId);
    }

    /**
     * | calling function of the for geting the top root parent
        | serial No : 03.01
        | Closed
     */
    public function getParent($parentId)
    {
        $mMenuMaster = new MenuMaster();
        $refvalue = $mMenuMaster->getMenuById($parentId);
        if ($refvalue['parent_id'] > 0) {
            $this->getParent($refvalue['parent_id']);
        }
        return $refvalue['id'];
    }
}













































/**
    | Working code for he tree structure
 */
// public function generateMenuTree($req)
// {
//     $mMenuMaster = new MenuMaster();
//     $mMenues = $mMenuMaster->fetchAllMenues();
//     $mRoleMenues = $mMenuMaster->getMenuByRole($req->roleId);

//     $data = collect($mMenues)->map(function ($value, $key) {
//         $return = array();
//         $return['id'] = $value['id'];
//         $return['parentId'] = $value['parent_id'];
//         $return['path'] = $value['route'];  ----------------------------> remove first the check
//         $return['name'] = $value['menu_string'];
//         $return['children'] = array();
//         return ($return);
//     });

//     $data = (objToArray($data));
//     $itemsByReference = array();

//     foreach ($data as $key => &$item) {
//         $itemsByReference[$item['id']] = &$item;
//     }

//     # looping for the generation of child nodes / operation will end if the parentId is not match to id 
//     foreach ($data as $key => &$item)
//         if ($item['id'] && isset($itemsByReference[$item['parentId']]))
//             $itemsByReference[$item['parentId']]['children'][] = &$item;

//     # this loop is to remove the external loop of the child node ie. not allowing the child node to create its own treee
//     foreach ($data as $key => &$item) {
//         if ($item['parentId'] && isset($itemsByReference[$item['parentId']]))
//             unset($data[$key]);
//     }

//     $data = collect($data)->values();
//     $roleWise = collect($mRoleMenues)->map(function ($value) {
//         return $value['id'];
//     });
//     $retunProperValues = collect($data)->map(function ($value, $key) use ($roleWise) {
//         if ($roleWise->contains($value['id'])) {
//             return $value;
//         }
//     });

//     return responseMsgs(true, "OPERATION OK!", $retunProperValues->filter()->values(), "", "01", "308.ms", "POST", $req->deviceId);
// }
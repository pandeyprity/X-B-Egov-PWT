<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\MenuMaster;
use App\Models\Menu\MenuMasterHierarchy;
use App\Models\Menu\WfRolemenu;
use App\Models\User;
use App\Models\Workflows\WfRoleusermap;
use App\Repository\Menu\Concrete\MenuRepo;
use App\Repository\Menu\Interface\iMenuRepo;
use Database\Seeders\UserSeeder;
use Exception;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * | Created On-23-11-2022 
     * | Created By-Anshu Kumar
     * | Updated By-Sam Kerketta
     * | Created for the Menus Operations
     * | Status : Open
     */

    protected $_repo;
    public function __construct(iMenuRepo $repo)
    {
        $this->_repo = $repo;
    }

    /**
     * |--------------------- Get the list of menues that are child Nodes ---------------------|
     * | @param 
     * | @var mMenuMaster model
     * | @var refmenues get menu list
     * | @var menues shorted menues
     * | @var listedMenues collecting the menu Parent
     * | @var value final List of menues
     * | @return listedMenues returning values
        | Serial No : 01
        | Closed
     */
    public function getAllMenues(Request $request)
    {
        try {
            $mMenuMaster = new MenuMaster();
            $refmenues = $mMenuMaster->fetchAllMenues();
            $menues = $refmenues->sortByDesc("id");
            $listedMenues = collect($menues)->map(function ($value) use ($mMenuMaster) {
                if ($value['parent_serial'] != 0) {
                    $parent = $mMenuMaster->getMenuById($value['parent_serial']);
                    $parentName = $parent['menu_string'];
                    $value['parentName'] = $parentName;
                    return $value;
                }
                return $value;
            })->values();
            return responseMsgs(true, "List of Menues!", $listedMenues, "", "02", "", "GET", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Get Menu according to Roles ---------------------|
     * | @param req roleId
        | Serial No : 02
        | Closed
     */
    public function getMenuByRoles(Request $req)
    {
        try {
            return $this->_repo->getMenuByRoles($req);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Enable or Desable the menu for roles ---------------------|
     * | @param req roleId,menuId,status
        | Serial No : 03
        | Closed
     */
    public function updateMenuByRole(Request $req)
    {
        try {
            $req->validate([
                'roleId' => 'required|integer',
                'menuId' => 'required|integer',
                'status' => 'required|bool'
            ]);
            return $this->_repo->updateMenuByRole($req);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Adding new Menu ---------------------|
     * | @param request menuName,Route
     * | @var mMenuMaster Model
        | Serial NO : 04
        | Closed
     */
    public function addNewMenues(Request $request)
    {
        try {
            $request->validate([
                'menuName'      => 'required',
                'route'         => 'nullable',
            ]);
            $mMenuMaster = new MenuMaster();
            $mMenuMaster->putNewMenues($request);
            return responseMsgs(true, "Data Saved!", "", "", "02", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Soft Delition of the Menu in Menu Master ---------------------|
     * | @param request menu Id
     * | @var menuDeletion model
        | Serial No : 05
        | Closed
     */
    public function deleteMenuesDetails(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required'
            ]);
            $menuDeletion = new MenuMaster();
            $menuDeletion->softDeleteMenues($request->id);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Generate the menu tree srtucture ---------------------|
     * | @param request roleId -> opetional 
        | Serial No : 06
        | Closed
     */
    public function getTreeStructureMenu(Request $request)
    {
        try {
            return $this->_repo->generateMenuTree($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- List all parent Menu ---------------------|
     * | @var mMenuMaster model
        | Serial No : 07
        | Closed
     */
    public function listParentSerial()
    {
        try {
            $mMenuMaster = new MenuMaster();
            $parentMenu = $mMenuMaster->getParentMenue()->get();
            return responseMsgs(true, "parent Menu!", $parentMenu, "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Get the child of the menu  ---------------------|
     * | @param request
     * | @var mMenuMaster Model 
     * | @var listedChild List of chil nodes
     * | @return listedChild 
        | Serial No : 08
        | Closed
     */
    public function getChildrenNode(Request $request)
    {
        try {
            $mMenuMaster = new MenuMaster();
            $listedChild = $mMenuMaster->getChildrenNode($request->id)->get();
            return responseMsgs(true, "child Menu!", $listedChild, "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |--------------------- Update menu Master ---------------------|
     * | @param request
     * | @var mMenuMaster Model
        | Serial No : 09
        | Closed
     */
    public function updateMenuMaster(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'serial' => 'nullable|int',
            'parentSerial' => 'nullable|int',
            'route' => 'nullable|',
            'delete' => 'nullable|boolean'
        ]);
        try {
            $mMenuMaster = new MenuMaster();
            $mMenuMaster->updateMenuMaster($request);
            return responseMsgs(true, "Menu Updated!", "", "", "02", "733", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |--------------------- Get menu by Menu Id ---------------------|
     * | @param request menuId
     * | @var mMenuMaster model
     * | @var menues menu list
     * | @var parent list of parent 
     * | @var parentName collect the name of the parent node 
     * | @return menues list of menu according to menu id
        | Serial No : 10
        | Open
     */
    public function getMenuById(Request $request)
    {
        $request->validate([
            'menuId' => 'required|int'
        ]);
        try {
            $mMenuMaster = new MenuMaster();
            $menues = $mMenuMaster->getMenuById($request->menuId);
            if ($menues['parent_serial'] == 0) {
                return responseMsgs(true, "Menu List!", $menues, "", "01", "", "POST", "");
            }
            $parent = $mMenuMaster->getMenuById($menues['parent_serial']);
            $menues['parentName'] = $parent['menu_string'];
            return responseMsgs(true, "Menu List!", $menues, "", "01", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
        | Terminate
     */
    public function getRoleWiseMenu(Request $request)
    {
        try {
            $mWfRolemenu = new WfRolemenu();
            $menuList = $mWfRolemenu->getRoleWiseMenu();
            $checkExist = collect($menuList)->first()->id;
            if (!$checkExist) {
                throw new Exception("Menu Not found!");
            }
            return responseMsgs(true, "Role Waise Menu!", $menuList, "", "02", "", "", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //================================================================================================

    /**
     * | Created On-27-03-2023
     * | Modified By-Mrinal Kumar
     * | Created for the Menus Operations
     */

    /**
     * | Get Menu by module 
     */
    public function getMenuByModuleId(Request $req)
    {
        try {
            $user = authUser($req);
            $userId = $user->id;
            $mWfRoleUserMap = new WfRoleusermap();
            $mMenuRepo = new MenuRepo();
            $ulbId = $user->ulb_id;

            $ulbName =  User::select('ulb_name')
                ->join('ulb_masters', 'ulb_masters.id', 'users.ulb_id')
                ->where('ulb_id', $ulbId)
                ->where('users.id', $userId)
                ->first();

            $wfRole = $mWfRoleUserMap->getRoleDetailsByUserId($userId);
            $roleId = $wfRole->pluck('roleId');

            $mreqs = new Request([
                'roleId' => $roleId,
                'moduleId' => $req->moduleId
            ]);

            $treeStructure = $mMenuRepo->generateMenuTree($mreqs);
            $menu = collect($treeStructure)['original']['data'];

            $menuPermission['permission'] = $menu;
            $menuPermission['userDetails'] = [
                'userName' => $user->name,
                'ulb'      => $ulbName->ulb_name ?? 'No Ulb Assigned',
                'mobileNo' => $user->mobile,
                'email'    => $user->email,
                'imageUrl' => $user->photo_relative_path . '/' . $user->photo,
                'roles' => $wfRole->pluck('roles')                            # use in case of if the user has multiple roles
            ];
            return responseMsgs(true, "Parent Menu!", $menuPermission, "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }
}

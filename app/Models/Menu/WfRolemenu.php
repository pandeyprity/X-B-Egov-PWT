<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRolemenu extends Model
{
    use HasFactory;

    // Get All Menus by Role Id
    public function getMenues($req)
    {
        return WfRolemenu::where('role_id', $req->roleId)
            ->where('menu_id', $req->menuId)
            ->first();
    }

    /**
     * | Get menu By RoleId 
     */
    public function getMenuDetailsByRoleId($roleIds)
    {
        return WfRolemenu::join('menu_masters', 'menu_masters.id', '=', 'wf_rolemenus.menu_id')
            ->where('wf_rolemenus.role_id', $roleIds)
            ->where('wf_rolemenus.status', 1)
            ->select(
                'menu_masters.menu_string AS menuName',
                'menu_masters.route AS menuPath',
            )
            ->orderByDesc('menu_masters.id')
            ->get();
    }

    /**
     * | get menu according to role and user 
     */
    public function getRoleWiseMenu()
    {
        WfRolemenu::select(
            'menu_masters.id',
            'menu_masters.menu_string AS menuName',
            'menu_masters.route',
        )
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_rolemenus.role_id')
            ->join('menu_masters', 'menu_masters.id', '=', 'wf_rolemenus.menu_id')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_rolemenus.role_id')
            ->where('wf_roleusermaps.user_id', auth()->user()->id)
            ->where('wf_rolemenus.status', true)
            ->where('menu_masters.is_deleted', false)
            ->where('wf_roles.is_suspended', false)
            ->where('wf_roleusermaps.is_suspended', false)
            ->get();
    }

    /**
     * | Get menu By RoleId 
     */
    public function getMenuListByRoleId($roleIds)
    {
        return WfRolemenu::select(
            'm.menu_name AS menuName',
            'm.route AS menuPath',
            '*'
        )
            ->join('menu_master_hierarchies as m', 'm.id', '=', 'wf_rolemenus.menu_id')
            ->where('wf_rolemenus.role_id', $roleIds)
            ->where('wf_rolemenus.status', 1)
            ->orderByDesc('m.id')
            ->get();
    }
}

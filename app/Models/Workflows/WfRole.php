<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WfRole extends Model
{
    use HasFactory;

    //add role
    public function addRole($req)
    {
        $createdBy = Auth()->user()->id;
        $role = new WfRole;
        $role->role_name = $req->roleName;
        $role->created_by = $createdBy;
        $role->stamp_date_time = Carbon::now();
        $role->save();
    }

    //update role
    public function updateRole($req)
    {
        $createdBy = Auth()->user()->id;
        $role = WfRole::find($req->id);
        $role->role_name = $req->roleName;
        $role->is_suspended = $req->isSuspended;
        $role->created_by = $createdBy;
        $role->updated_at = Carbon::now();
        $role->save();
    }

    //role by id
    public function rolebyId($req)
    {
        return  WfRole::where('id', $req->id)
            ->where('status', 1)
            ->get();
    }

    //role list
    public function roleList()
    {
        return  WfRole::where('status', 1)
            ->orderByDesc('id')
            ->get();
    }

    //delete role

    public function deleteRole($req)
    {
        $data = WfRole::find($req->id);
        $data->status = 0;
        $data->save();
    }
}

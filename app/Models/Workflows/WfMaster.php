<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WfMaster extends Model
{
    use HasFactory;
    public $timestamps = false;


    //create master
    public function addMaster($req)
    {
        $createdBy = Auth()->user()->id;
        $data = new WfMaster;
        $data->workflow_name = $req->workflowName;
        $data->created_by = $createdBy;
        $data->module_id = $req->moduleId;
        $data->stamp_date_time = Carbon::now();
        $data->created_at = Carbon::now();
        $data->save();
    }

    ///update master list
    public function updateMaster($req)
    {
        $data = WfMaster::find($req->id);
        $data->workflow_name = $req->workflowName;
        $data->module_id = $req->moduleId;
        $data->save();
    }


    //list by id
    public function listById($req)
    {
        $list = WfMaster::where('id', $req->id)
            ->where('is_suspended', false)
            ->get();
        return $list;
    }

    //all master list
    public function listMaster()
    {
        $list = WfMaster::select(
            'wf_masters.id',
            'workflow_name',
            'module_name',
            'module_id'
        )
            ->leftJoin('module_masters', 'module_masters.id', 'wf_masters.module_id')
            ->where('wf_masters.is_suspended', false)
            ->orderByDesc('wf_masters.id')
            ->get();
        return $list;
    }


    //delete master
    public function deleteMaster($req)
    {
        $data = WfMaster::find($req->id);
        $data->is_suspended = "true";
        $data->save();
    }
}

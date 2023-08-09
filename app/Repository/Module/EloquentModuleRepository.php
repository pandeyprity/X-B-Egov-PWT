<?php

namespace App\repository\Module;

use Illuminate\Http\Request;
use App\Repository\Module\ModuleRepository;
use App\Models\ModuleMaster;
use Exception;

/**
 * Repository for saving, editing and fetching Modules 
 */

class EloquentModuleRepository implements ModuleRepository
{
    /**
     * Storing Modules in database
     * Validate First 
     * Check if the module is already existing or not 
     * Save 
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     */
    public function store(Request $request)
    {
        $request->validate([
            'module_name' => 'required|unique:module_masters'
        ]);

        try {
            $module = new ModuleMaster;
            $module->module_name = $request->module_name;
            $module->save();
            return response()->json('Successfully Saved', 200);
        } catch (Exception $e) {
            return response($e, 400);
        }
    }

    public function show($id)
    {
        try {
            $module = ModuleMaster::where('id', $id)->get();
            $arr = array();
            foreach ($module as $modules) {
                $val['id'] = $modules->id ?? '';
                $val['module_name'] = $modules->module_name ?? '';
                $val['deleted_at'] = $modules->deleted_at ?? '';
                $val['created_at'] = $modules->created_at ?? '';
                $val['updated_at'] = $modules->updated_at ?? '';
                array_push($arr, $val);
            }
            return $arr;
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Fetching all modules from database
     */
    public function create()
    {
        $module = ModuleMaster::orderByDesc('id')->get();
        $arr = array();
        foreach ($module as $modules) {
            $val['id'] = $modules->id ?? '';
            $val['module_name'] = $modules->module_name ?? '';
            $val['deleted_at'] = $modules->deleted_at ?? '';
            $val['created_at'] = $modules->created_at ?? '';
            $val['updated_at'] = $modules->updated_at ?? '';
            array_push($arr, $val);
        }
        return response()->json($arr, 200);
    }

    /**
     * Updating Modules 
     * Validate first
     * Check if the module is alreay existing or not 
     * Update
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * 
     */
    public function update(Request $request, $id)
    {
        /**
         * 
         * @param Illuminate\Http\Request
         * @param Illuminate\Http\Request $request
         */

        $request->validate([
            'module_name' => 'required|unique:module_masters'
        ]);

        try {
            $module = ModuleMaster::find($id);
            $module->module_name = $request->module_name;
            $module->save();
            return response()->json('Successfully Updated', 200);
        } catch (Exception $e) {
            return response($e, 400);
        }
    }

    /**
     * Deleting Module by ModuleID
     * @param id
     * @return response
     */
    public function destroy($id)
    {
        try {
            $module = ModuleMaster::find($id);
            $module->delete();
            return response()->json('Successfully Deleted', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}

<?php

namespace App\Traits\Api;

/**
 * Trait for Saving and updating Api 
 * Created On-29-06-2022 
 * Created By-Anshu Kumar
 * 
 * Code Tested By-
 * Feedback-
 */
trait StoreApi
{
    // Store Data
    public function saving($api_master, $request)
    {
        $api_master->description = $request->description;
        $api_master->remarks = $request->remarks;
        $api_master->category = $request->category;
        $api_master->end_point = $request->endPoint;
        $api_master->usage = $request->usage;
        $api_master->pre_condition = $request->preCondition;
        $api_master->request_payload = json_encode($request->requestPayload);
        $api_master->response_payload = json_encode($request->responsePayload);
        $api_master->post_condition = $request->postCondition;
        $api_master->version = $request->version;
        $api_master->tags = $request->tags;
        $api_master->created_on = $request->createdOn;
        $api_master->created_by = $request->createdBy;
        $api_master->revision_no = $request->revisionNo;
        $api_master->discontinued = $request->discontinued;
        $api_master->save();
        return response()->json(['status' => true, 'Message' => "Successfully Saved"], 200);
    }

    // Get api details
    public function getApiDetails($api)
    {
        $arr = array();
        foreach ($api as $apis) {
            $val['id'] = $apis->id ?? '';
            $val['description'] = $apis->description ?? '';
            $val['remarks'] = $apis->remarks ?? '';
            $val['tags'] = $apis->tags ?? '';
            $val['category'] = $apis->category ?? '';
            $val['end_point'] = $apis->end_point ?? '';
            $val['usage'] = $apis->usage ?? '';
            $val['pre_condition'] = $apis->pre_condition ?? '';
            $val['request_payload'] = json_decode($apis->request_payload) ?? '';
            $val['response_payload'] = json_decode($apis->response_payload) ?? '';
            $val['post_condition'] = $apis->post_condition ?? '';
            $val['version'] = $apis->version ?? '';
            $val['created_on'] = $apis->created_on ?? '';
            $val['created_by'] = $apis->created_by ?? '';
            $val['revision_no'] = $apis->revision_no ?? '';
            $val['discontinued'] = $apis->discontinued ?? '';
            $val['created_at'] = $apis->created_at ?? '';
            $val['updated_at'] = $apis->updated_at ?? '';
            array_push($arr, $val);
        }
        return response($arr, 200);
    }
}

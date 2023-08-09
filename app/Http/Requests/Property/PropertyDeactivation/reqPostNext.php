<?php

namespace App\Http\Requests\Property\PropertyDeactivation;

use App\Http\Requests\Property\PropertyRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class reqPostNext extends PropertyRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $mRegex     = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        $refWorkflowId = Config::get('workflow-constants.PROPERTY_DEACTIVATION_WORKFLOW_ID');
        $common = new \App\Repository\Common\CommonFunction(); 
        $rolse = (collect($common->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true))->implode("id",","));
        return [
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'senderRoleId' => "required|integer|in:$rolse",
            'receiverRoleId' => "required|integer|in:$rolse|different:senderRoleId",
            'comment' => "required|min:10|regex:$mRegex",
        ];
    }
    
}

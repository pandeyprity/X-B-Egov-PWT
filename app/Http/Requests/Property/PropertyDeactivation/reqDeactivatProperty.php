<?php

namespace App\Http\Requests\Property\PropertyDeactivation;

use App\Http\Requests\Property\PropertyRequest;
use Carbon\Carbon;

class reqDeactivatProperty extends PropertyRequest
{
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $mRegex     = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
        $mNowDate   = Carbon::now()->format("Y-m-d");
        return [
            "propertyId"=>"required|digits_between:1,9223372036854775807",
            "comments"  => "required|min:10|regex:$mRegex",
            "document"  =>"required|mimes:pdf,jpg,jpeg,png|max:2048",
        ];
    }
    
}

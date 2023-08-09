<?php

namespace App\Http\Requests\Property\PropertyDeactivation;

use App\Http\Requests\Property\PropertyRequest;

class reqReadProperty extends PropertyRequest
{
   
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "propertyId"=>"required|digits_between:1,9223372036854775807",
        ];
    }
}

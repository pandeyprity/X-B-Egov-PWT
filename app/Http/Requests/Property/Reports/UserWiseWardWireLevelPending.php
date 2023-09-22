<?php

namespace App\Http\Requests\Property\Reports;

use Illuminate\Foundation\Http\FormRequest;

class UserWiseWardWireLevelPending extends Levelformdetail
{
    public function __construct()
    {
        
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules["userId"] = "required|digits_between:1,9223372036854775807";
        return $rules;
    }
}

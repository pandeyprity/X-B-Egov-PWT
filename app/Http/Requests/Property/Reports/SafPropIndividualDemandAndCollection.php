<?php

namespace App\Http\Requests\Property\Reports;

use App\Http\Requests\AllRequest;
use Illuminate\Foundation\Http\FormRequest;

class SafPropIndividualDemandAndCollection extends AllRequest
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
        $rules = [
            "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
            "key" => "nullable|regex:/^[^<>{};:.,~!?@#$%^=&*\"]*$/i",
            "wardId" => "nullable|digits_between:1,9223372036854775807",
            "page" => "nullable|digits_between:1,9223372036854775807",
            "perPage" => "nullable|digits_between:1,9223372036854775807",
        ];
        return $rules ;
    }
}

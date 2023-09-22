<?php

namespace App\Http\Requests\Property\Reports;

use App\Http\Requests\AllRequest;

class CollectionReport extends AllRequest
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
            "fromDate" => "required|date|date_format:Y-m-d",
            "uptoDate" => "required|date|date_format:Y-m-d",
            "wardId" => "nullable|digits_between:1,9223372036854775807",
            "userId" => "nullable|digits_between:1,9223372036854775807",
            "paymentMode" => "nullable",
            "page" => "nullable|digits_between:1,9223372036854775807",
            "perPage" => "nullable|digits_between:1,9223372036854775807",
            "all" => "nullable|in:1,0",
            "footer" => "nullable|in:1,0",
        ];
        return $rules;
    }
}

<?php

namespace App\Http\Requests\water;

use Illuminate\Foundation\Http\FormRequest;

class colllectionReport extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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
            "zoneId" => "nullable|digits_between:1,9223372036854775807",
            "userId" => "nullable|digits_between:1,9223372036854775807",
            "paymentMode" => "nullable",
            "page" => "nullable|digits_between:1,9223372036854775807",
            "perPage" => "nullable|digits_between:1,9223372036854775807",
            "all" => "nullable|in:1,0",
            "footer" => "nullable|in:1,0",
            "meterStatus"=> "nullable|in:1,3"
        ];
        return $rules;
    }
}

<?php

namespace App\Http\Requests\Property\Akola;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApplySafReq extends FormRequest
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
        $todayDate = Carbon::now()->format('Y-m-d');
        $validation = [
            "propertyType" => "required|integer",
            "areaOfPlot" => "required|numeric",
            "category" => "required|integer",
            "dateOfPurchase" => "required|date|date_format:Y-m-d|before_or_equal:$todayDate",
            "owner" => "required|array",
            "owner.*.gender" => "required|In:Male,Female,Transgender",
            "owner.*.dob" => "required|date|date_format:Y-m-d|before_or_equal:$todayDate",
            "owner.*.guardianName" => "required|string",
            "owner.*.relation" => "required|string|in:S/O,W/O,D/O,C/O",
            "owner.*.mobileNo" => "required|digits:10|regex:/[0-9]{10}/",
            "owner.*.aadhar" => "digits:12|regex:/[0-9]{12}/|nullable",
            "owner.*.pan" => "string|nullable",
            "owner.*.email" => "email|nullable",
            "owner.*.isArmedForce" => "required|bool",
            "owner.*.isSpeciallyAbled" => "required|bool"
        ];

        if ($this->propertyType != 4) {
            $validation = array_merge($validation, [
                "floor" => "required|array",
                "floor.*.floorNo" => "required|integer",
                "floor.*.constructionType" => "required|integer",
                "floor.*.usageType" => "required|integer",
                "floor.*.buildupArea" => "required|numeric",
            ]);
        }
        return $validation;
    }

    // Validation Error Message
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors(),
                    'errors' => $validator->errors()
                ],
                422
            )
        );
    }
}

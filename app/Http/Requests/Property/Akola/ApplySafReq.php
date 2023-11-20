<?php

namespace App\Http\Requests\Property\Akola;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApplySafReq extends FormRequest
{
    /**
     * | Used as Only Review Calculation
     */
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
            "propertyType" => "required|integer",
            "areaOfPlot" => "required|numeric",
            "category" => "required|integer",
            "dateOfPurchase" => "required|date|date_format:Y-m-d|before_or_equal:$todayDate",
            "owner" => "nullable|array",
            "owner.*.gender" => "nullable|In:Male,Female,Transgender",
            "owner.*.dob" => "nullable|date|date_format:Y-m-d|before_or_equal:$todayDate",
            "owner.*.guardianName" => "nullable|string",
            "owner.*.relation" => "nullable|string|in:S/O,W/O,D/O,C/O",
            "owner.*.mobileNo" => "nullable|digits:10|regex:/[0-9]{10}/",
            "owner.*.aadhar" => "digits:12|regex:/[0-9]{12}/|nullable",
            "owner.*.pan" => "string|nullable",
            "owner.*.email" => "email|nullable",
            "owner.*.isArmedForce" => "nullable|bool",
            "owner.*.isSpeciallyAbled" => "nullable|bool"
        ];
        if (isset($this->assessmentType) && $this->assessmentType != 1 && $this->assessmentType != 5) {           // Holding No Required for Reassess,Mutation,Bifurcation,Amalgamation
            $validation['previousHoldingId'] = "required|numeric";
        }

        if ($this->propertyType != 4) {
            $validation = array_merge($validation, [
                "floor" => "required|array",
                "floor.*.floorNo" => "required|integer",
                "floor.*.constructionType" => "required|integer",
                "floor.*.usageType" => "required|integer",
                "floor.*.buildupArea" => "required|numeric",
                // "floor.*.dateFrom" => "required|date|date_format:Y-m-d|before_or_equal:$this->todayDate|after_or_equal:$this->dateOfPurchase"
                "floor.*.dateFrom" => "required|date|date_format:Y-m-d|before_or_equal:$this->todayDate".($this->assessmentType==3?"":"|after_or_equal:$this->dateOfPurchase")
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
                200
            )
        );
    }
}

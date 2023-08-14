<?php

namespace App\Http\Requests\Property\Akola;

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
        $validation = [
            "propertyType" => "required|integer",
            "areaOfPlot" => "required|numeric",
            "category" => "required|integer",
            "dateOfPurchase" => "required|date|date_format:Y-m-d"
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

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
            "propertyDate" => "required|date|date_format:Y-m-d"
        ];

        if ($this->propertyType != 4) {
            $validation = array_merge($validation, [
                "floors" => "required|array",
                "floors.*.floorNo" => "required|integer",
                "floors.*.constructionType" => "required|integer",
                "floors.*.usageType" => "required|integer",
                "floors.*.builupArea" => "required|numeric",
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

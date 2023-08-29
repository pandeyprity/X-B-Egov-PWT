<?php

namespace App\Http\Requests\Bandobastee;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'settlerName' => 'required|string|max:255',
            'mobileNo' => 'required|numeric|digits:10',
            'panNo' => 'nullable|string|max:10',
            'gstNo' => 'nullable|string',
            'settlementFrom' => 'required|date_format:Y-m-d',
            'settlementUpto' => 'required|date_format:Y-m-d',
            'baseAmount' => 'required|numeric',
            'emdAmount' => 'required|numeric',
            'standCategoryId' => 'nullable|integer',
            'standId' => 'nullable|integer',
            'parkingId' => 'nullable|integer',
            'bazarId' => 'nullable|integer',
            'banquetHallId' => 'nullable|integer',
            'bandobasteeType' => 'required|integer',
            'remarks' => 'nullable|string',
        ];
    }

      
    /**
     * | Error Message
     */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ], 422),);
    }
}

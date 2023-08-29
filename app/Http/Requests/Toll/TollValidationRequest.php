<?php

namespace App\Http\Requests\Toll;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TollValidationRequest extends FormRequest
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
            'circleId'              =>   'required|integer',
            'tollNo'                =>   'nullable|string',
            // 'tollType'              =>   'required|regex:/^[A-Za-z ]+$/',
            'vendorName'            =>   'required|regex:/^[A-Za-z ]+$/',
            'address'               =>   'required|string|max:255',
            'rate'                  =>   'required|numeric',
            'lastPaymentDate'       =>   'nullable|date',
            'lastAmount'            =>   'nullable|numeric',
            'marketId'              =>   'required|integer',
            'presentLength'         =>   'nullable|string',
            'presentBreadth'        =>   'nullable|string',
            'presentHeight'         =>   'nullable|string',
            'noOfFloors'            =>   'nullable|string',
            'tradeLicense'          =>   'nullable|string',
            'construction'          =>   'nullable|string',
            'utility'               =>   'nullable|string',
            'mobile'                =>   'numeric|digits:10',
            'remarks'               =>   'nullable|string',
            'photograph1'           =>   'required|image|mimes:jpeg,png,jpg',
            'photograph2'           =>   'required|image|mimes:jpeg,png,jpg',
            'longitude'             =>   'nullable|string',
            'latitude'              =>   'nullable|string',
            'userId'                =>   'nullable|numeric',
            'lastTranId'            =>   'nullable|numeric',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ], 422),);
    }
}

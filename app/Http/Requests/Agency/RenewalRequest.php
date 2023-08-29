<?php

namespace App\Http\Requests\Agency;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RenewalRequest extends FormRequest
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
            'ulbId' => 'required|numeric',
            'applicationId' => 'required|integer',
            'entityType' => 'required|integer',
            'entityName' => 'required|string',
            'address' => 'required|string',
            'mobileNo' => 'required|numeric|digits:10',
            'officeTelephone' => 'required|numeric',
            'fax' => 'required',
            'email' => 'required|email',
            'panNo' => 'required|string',
            'gstNo' => 'required|string',
            'blacklisted' => 'required|bool',
            'pendingCourtCase' => 'required|bool',
            'documents' => 'required|array',
            'documents.*.image' => 'required|mimes:png,jpeg,pdf,jpg',
            'documents.*.docCode' => 'required|string',
            'documents.*.ownerDtlId' => 'nullable|integer'
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

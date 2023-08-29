<?php

namespace App\Http\Requests\SelfAdvets;

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
            'ulbId' => 'required|integer',
            'applicantName' => 'required|string',
            'applicationId' => 'required|integer',
            'licenseYear' => 'required',
            'fatherName' => 'required',
            'email' => 'nullable|email',
            'residenceAddress' => 'required',
            'wardId' => 'required|integer',
            'permanentAddress' => 'required',
            'permanentWardId' => 'required|integer',
            'entityName' => 'required',
            'entityAddress' => 'required',
            'entityWardId' => 'required|integer',
            'mobileNo' => 'required|numeric|digits:10',
            'aadharNo' => 'required|numeric|digits:12',
            'tradeLicenseNo' => 'nullable|string',
            'holdingNo' => 'nullable|string',
            'gstNo' => 'nullable|string',
            // 'longitude' => 'nullable|numeric',
            // 'latitude' => 'nullable|numeric',
            'displayArea' => 'required|numeric',
            'displayType' => 'required|integer',
            'installationLocation' => 'required|integer',
            'brandDisplayName' => 'required',
            'advtCategory' => 'required|integer',
            'documents' => 'required|array',
            'documents.*.image' => 'required|mimes:png,jpeg,pdf,jpg',
            'documents.*.docCode' => 'required|string',
            'documents.*.ownerDtlId' => 'nullable|integer'
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

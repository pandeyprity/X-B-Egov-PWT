<?php

namespace App\Http\Requests\PrivateLand;

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
            'applicant' => 'required',
            'father' => 'required',
            'email' => 'required|email',
            'residenceAddress' => 'required',
            'wardId' => 'required|integer',
            'permanentAddress' => 'required',
            'permanentWardId' => 'required|integer',
            'mobileNo' => 'required|numeric|digits:10',
            'aadharNo' => 'required|numeric|digits:12',
            'licenseFrom' => 'required|date_format:Y-m-d',
            'licenseTo' => 'required|date_format:Y-m-d',
            'holdingNo' => 'required|string',
            'tradeLicenseNo' => 'required|string',
            'gstNo' => 'required',
            'entityName' => 'required|string',
            'entityAddress' => 'required|string',
            'brandDisplayName' => 'required',
            'brandDisplayAddress' => 'required',
            'brandDisplayHoldingNo' => 'required',
            'entityWardId' => 'required|integer',
            'displayArea' => 'required|numeric',
            'displayType' => 'required|integer',
            'noOfHoardings' => 'required|integer',
            // 'longitude' => 'required|numeric',
            // 'latitude' => 'required|numeric',
            'installationLocation' => 'required|integer',
            'ulbId' => 'required|integer',
            'typology' => 'required|integer',

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

<?php

namespace App\Http\Requests\Vehicles;

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
            'mobile' => 'required|numeric|digits:10',
            'aadharNo' => 'required|numeric|digits:12',
            'licenseFrom' => 'required|date_format:Y-m-d',
            'licenseTo' => 'required|date_format:Y-m-d',
            'entityName' => 'required',
            'tradeLicenseNo' => 'nullable|string',
            'gstNo' => 'required|string',
            'vehicleName' => 'required|string',
            'vehicleNo' => 'required|string',
            'vehicleType' => 'required|integer',
            'ulbId' => 'required|integer',
            'brandDisplayed' => 'required|string',
            'frontArea' => 'required|numeric',
            'rearArea' => 'required|numeric',
            'topArea' => 'required|numeric',
            'displayType' => 'required|integer',
            'typology' => 'required|integer',
            // 'aadharDoc' => 'required|mimes:png,jpeg,pdf,jpg',
            // 'tradeDoc' => 'nullable|mimes:png,jpeg,pdf,jpg',
            // 'vehiclePhotoDoc' => 'nullable|mimes:png,jpeg,pdf,jpg',
            // 'ownerBookDoc' => 'nullable|mimes:png,jpeg,pdf,jpg',
            // 'insuranceDoc' => 'nullable|mimes:png,jpeg,pdf,jpg',
            // 'gstDoc' => 'nullable|mimes:png,jpeg,pdf,jpg',
            // 'drivingLicenseNo' => 'nullable|mimes:png,jpeg,pdf,jpg'
            // 'documents' => 'required|array',
            // 'documents.*.id' => 'required|integer',
            // 'documents.*.image' => 'required|mimes:png,jpeg,pdf,jpg',
            // 'documents.*.relativeName' => 'required|string'

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

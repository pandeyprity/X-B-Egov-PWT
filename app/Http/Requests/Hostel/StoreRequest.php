<?php

namespace App\Http\Requests\Hostel;

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
            'rule' => 'required|string',
            'applicantName' => 'required|string',
            'fatherName' => 'required|string',
            'mobile' => 'required|numeric|digits:10',
            'email' => 'required|email',
            'residentialAddress' => 'required|string',
            'residentialWardId' => 'required|integer',
            'permanentAddress' => 'required|string',
            'permanentWardId' => 'required|integer',

            'licenseYear' => 'required|integer',
            'entityName' => 'required|string',
            'entityAddress' => 'required|string',
            'entityWardId' => 'required|integer',
            'holdingNo' => 'required|string',
            'tradeLicenseNo' => 'required|string',
            'organizationType' => 'required|integer',
            'landDeedType' => 'required|integer',
            'waterSupplyType' => 'required|integer',
            'electricityType' => 'required|integer',
            'securityType' => 'required|integer',
            'hostelType' => 'required|integer',
            'messType' => 'required|integer',
            'cctvCamera' => 'required|integer',
            'noOfBeds' => 'required|integer',
            'noOfRooms' => 'required|integer',
            'fireExtinguisher' => 'required|integer',
            'entryGate' => 'required|integer',
            'exitGate' => 'required|integer',
            'twoWheelersParking' => 'required|integer',
            'fourWheelersParking' => 'required|integer',
            'aadharCard' => 'required|integer',
            'panCard' => 'required|string',
            'ulbId' => 'required|integer',
            // 'isSchoolCollegeUniv' => 'required|boolean',
            // 'schoolCollegeUnivName' => $this->isSchoolCollegeUniv==true?'required|String':'nullable|string',
            // 'isApproveByGovt' => $this->isSchoolCollegeUniv==true?'required|boolean':'nullable|boolean',
            'isApproveByGovt' =>'required|boolean',
            // 'govtType' => $this->isApproveByGovt==true?'required|String':'nullable|string',

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

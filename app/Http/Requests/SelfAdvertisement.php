<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelfAdvertisement extends FormRequest
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
        return [
            'licenseYear' => 'required',
            'applicant' => 'required',
            'father' => 'required',
            'email' => 'required|email',
            'mobile' => 'required',
            'aadharNo' => 'required|int',
            'entityName' => 'required',
            'entityAddress' => 'required'
        ];
    }
}

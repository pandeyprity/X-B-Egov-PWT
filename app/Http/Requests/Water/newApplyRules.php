<?php

namespace App\Http\Requests\Water;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class newApplyRules extends FormRequest
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
     * Check the ulb_id for the case of jsk
     * @return array
     */
    public function rules()
    {
        $rules['connectionTypeId']      = 'required|int|in:1,2';
        $rules['propertyTypeId']        = 'required|int|in:1,2,3,4,6,7';
        $rules['ownerType']             = 'required|int|in:1,2';
        $rules['wardId']                = 'required|int';
        $rules['areaSqft']              = 'required|numeric';
        $rules['pin']                   = "required|digits:6|regex:/^([0-9\s\-\+\(\)]*)$/|";
        $rules['connection_through']    = 'required|int|in:1,2';
        $rules['ulbId']                 = 'required|int';
        $rules['owners']                = "required|array";

        if (isset($this->owners) && $this->owners) {
            $rules["owners.*.ownerName"]    = "required";
            $rules["owners.*.mobileNo"]     = "required|digits:10|regex:/[0-9]{10}/";
            $rules["owners.*.email"]        = "nullable|email";
        }
        if (isset($this->connection_through) && $this->connection_through == 1) {
            $rules['holdingNo'] = 'required|';
        }
        if (isset($this->connection_through) && $this->connection_through == 2) {
            $rules['safNo'] = 'required|';
        }
        return $rules;
    }

    // Validation Error Message
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'status'   => false,
                    'message'  => 'The given data was invalid',
                    'errors'   => $validator->errors()
                ],
                422
            )
        );
    }
}

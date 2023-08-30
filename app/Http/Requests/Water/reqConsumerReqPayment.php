<?php

namespace App\Http\Requests\Water;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Config;

class reqConsumerReqPayment extends FormRequest
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
        $rules = array();
        $paymentModes = Config::get('payment-constants.PAYMENT_MODE');

        if (isset($this['paymentMode']) && in_array($this['paymentMode'], $paymentModes) && $this['paymentMode'] != $paymentModes['3'] && $this['paymentMode'] != $paymentModes['1']) {
            $rules['chequeDate']    = "required|date|date_format:Y-m-d";
            $rules['bankName']      = "required";
            $rules['branchName']    = "required";
            $rules['chequeNo']      = "required";
        }
        $rules['applicationId']     = 'required';
        $rules['paymentMode']       = 'required|';
        $rules['remarks']           = 'required|';
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

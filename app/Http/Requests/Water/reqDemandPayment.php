<?php

namespace App\Http\Requests\Water;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Config;

class reqDemandPayment extends FormRequest
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
        $offlinePaymentModes = Config::get('payment-constants.PAYMENT_OFFLINE_MODE_WATER');
        $refPaymentMode = Config::get('payment-constants.PAYMENT_OFFLINE_MODE');
        $refDate = Carbon::now()->format('Y-m-d');

        if (isset($this['paymentMode']) &&  in_array($this['paymentMode'], $offlinePaymentModes) && $this['paymentMode'] != $refPaymentMode['1']) {
            $rules['chequeDate']    = "required|date|date_format:Y-m-d";
            $rules['bankName']      = "required";
            $rules['branchName']    = "required";
            $rules['chequeNo']      = "required";
            if (isset($this['chequeDate']) && $this['chequeDate'] > $refDate) {
                # throw error
            }
        }
        $rules['consumerId']        = 'required';
        $rules['amount']            = 'required';
        $rules['demandUpto']        = 'required|date_format:Y-m-d|';
        $rules['demandFrom']        = 'required|date_format:Y-m-d|';
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

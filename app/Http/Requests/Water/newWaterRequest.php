<?php

namespace App\Http\Requests\water;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class newWaterRequest extends FormRequest
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
        $rules['Zone']                  = 'nullable';
        $rule['ulbId']                  = 'required|numeric';
        $rules['PropertyNo']            = 'required';
        $rules['MobileNo']               = 'required|numeric';
        $rules['Address']                = 'required';
        $rules['PoleLandmark']           = "required";
        $rules['DtcCode']                = 'nullable';
        $rules['MeterMake']              = 'nullable';
        $rules['MeterNo']                = "required";
        $rules['MeterDigit']             = 'nullable';
        $rules['MeterCategory']          = "nullable";
        $rule['TabSize']                 = "required|numeric";
        $rule['MeterState']              = "nullable";
        $rule['MeterReadig']             = 'nullable|numeric';
        $rule['ReadingDate']             = 'nullable';
     $rules = [
    'ConnectionDate' => 'nullable|date_format:Y-m-d',
];

        $rule['DisconnectionDate']       = 'nullable|date_format:Y-m-d';
        $rule['DisconnedReading']        = "nullable";
        $rule['BookNo']                   = 'nullable';
        $rule['FolioNo']                  = "nullable";
        $rule['BuildingNo']               = 'nullable';
        $rule['NoOfConnection']           = "nullable";
        $rule['IsMeterRented']            = "nullable";
        $rule['RentAmount']               = "nullable";
        $rule['TotalAmount']              = 'nullable';
        $rule['NearestConsumerNo']        = 'nullable';
        $rules['initial_meter']           = 'nullable';
        $rules['ownerName']               = 'required';
        $rules['GuardianName']            = 'required';
        $rules['Email']                   ='required';
        $rules['Category']                = 'required';
        $rules['PropertyType']            ='required';
        $rules['IsMeterWorking']          = 'nullbale';
        $rules['ConnectionId']            ="required";
        $rules['propertyNoType']          ="required";
        return $rules;
    }



    //     if (isset($this->owners) && $this->owners) {
    //         $rules["owners.*.ownerName"]    = "required";
    //         $rules["owners.*.mobileNo"]     = "required|digits:10|regex:/[0-9]{10}/";
    //         $rules["owners.*.email"]        = "nullable|email";
    //     }
    //     if (isset($this->connection_through) && $this->connection_through == 1) {
    //         $rules['holdingNo'] = 'required|';
    //     }
    //     if (isset($this->connection_through) && $this->connection_through == 2) {
    //         $rules['safNo'] = 'required|';
    //     }
    //     return $rules;
    // }

    //    Validation Error Message
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

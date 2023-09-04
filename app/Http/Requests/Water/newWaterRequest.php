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
        $rules['Zone']                  = 'required';
        $rule['ulbId']                  = 'required|numeric';
        $rules['PropertyNo']            = 'required';
        $rules['MobileNo']               = 'required|numeric';
        $rules['Address']                = 'required';
        $rules['PoleLandmark']           = "required";
        $rules['DtcCode']                = 'required';
        $rules['MeterMake']              = 'required';
        $rules['MeterNo']                = "required";
        $rules['MeterDigit']             = 'required';
        $rules['MeterCategory']          = "required";
        $rule['TabSize']                 = "required|numeric";
        $rule['MeterState']              = "required";
        $rule['MeterReadig']             = 'required|numeric';
        $rule['ReadingDate']             = 'required';
     $rules = [
    'ConnectionDate' => 'required|date_format:Y-m-d',
];

        $rule['DisconnectionDate']       = 'required|date_format:Y-m-d';
        $rule['DisconnedReading']        = "required";
        $rule['BookNo']                   = 'required';
        $rule['FolioNo']                  = "required";
        $rule['BuildingNo']               = 'required';
        $rule['NoOfConnection']           = "nullable";
        $rule['IsMeterRented']            = "required";
        $rule['RentAmount']               = "required";
        $rule['TotalAmount']              = 'required';
        $rule['NearestConsumerNo']        = 'required';
        $rules['initial_meter']           = 'nullable';
        $rules['OwnerName']               = 'required';
        $rules['GuardianName']            = 'required';
        $rules['Email']                   ='required';
        $rules['Category']                = 'required';
        $rules['PropertyType']            ='required';
        $rules['IsMeterWorking']          ='required';
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

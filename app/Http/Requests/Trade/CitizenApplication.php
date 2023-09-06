<?php

namespace App\Http\Requests\Trade;

class CitizenApplication extends TradeRequest
{
    public function __construct()
    {
        parent::__construct();

    }
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
        $rules = [
            "citizenId" => "required|digits_between:1,9223372036854775807",
            "ulbId"    => "nullable|digits_between:1,92",
            "applicationType" => "nullable|string|in:".$this->_APPLYCATION_TYPE,
            "cotegory"=>"nullable|string|in:PENDIG,REJECT,APROVE,OLD",
            "status"=>"nullable|bool"
        ];
        return $rules;
    }
}

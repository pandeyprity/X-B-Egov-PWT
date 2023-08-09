<?php

namespace App\Http\Requests\Trade;

class ReqApplyDenail extends TradeRequest
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {       
        if($this->getMethod()=="GET")
            return[];
            
        $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
        $rules = [];
        $rules["firmName"]="required|regex:$regex";
        $rules["ownerName"]="required|regex:$regex";
        $rules["wardNo"]="required|int";
        $rules["holdingNo"]="required";
        $rules["address"]="required|regex:$regex";
        $rules["landmark"]="required|regex:$regex";
        $rules["city"]="required|regex:$regex";
        $rules["pinCode"]="required|digits:6";
        $rules["mobileNo"]="digits:10";
        $rules["comment"]="required|regex:$regex|min:10";
        $rules["document"]="required|mimes:pdf,jpg,jpeg,png|max:2048";
        return  $rules;
    }
}

<?php

namespace App\Http\Requests\Trade;

class ReqPaybleAmount extends TradeRequest
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
    # fjl
    public function rules()
    {
        $rules["applicationType"]   = "required|string";
        $rules["areaSqft"]          = "required|numeric";
        $rules["tocStatus"]         = "required|bool";
        $rules["firmEstdDate"]      = "required|date";
        $rules["licenseFor"]        = "required|int";
        $rules["natureOfBusiness"]  = "required|array";
        $rules["natureOfBusiness.*.id"] = "required|digits_between:1,9223372036854775807";
        if(isset($this->noticeDate) && $this->noticeDate)
        {
            $rules["noticeDate"] = "date";
        }
        return $rules;
    }
    public function messages()
    {
        $message["applicationType.required"]    = "Application Type Required";
        $message["areaSqft.required"]           = "Area is Required";
        $message["tocStatus.required"]          = "TocStatus is Required";
        $message["firmEstdDate.required"]       = "firmEstdDate is Required";
        $message["licenseFor.required"]         = "license For year is Required";
        return $message;
    }    
}

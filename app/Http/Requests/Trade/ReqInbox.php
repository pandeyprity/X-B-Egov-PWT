<?php

namespace App\Http\Requests\Trade;
class ReqInbox extends TradeRequest
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * Get the validation rules that apply to the request.safas
     *
     * @return array
     */
    # gk ggl
    public function rules()
    {
        return [
            "key"       =>  "nullable|string",
            "wardNo"    =>  "nullable|digits_between:1,9223372036854775807",
            "formDate"  =>  "nullable|date|date_format:Y-m-d",
            "toDate"    =>  "nullable|date|date_format:Y-m-d",
            "page"      =>  "nullable|digits_between:1,9223372036854775807",
            "perPage"      =>  "nullable|digits_between:1,9223372036854775807",
        ];
    }
}
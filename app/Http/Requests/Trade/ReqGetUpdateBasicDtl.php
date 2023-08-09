<?php

namespace App\Http\Requests\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ReqGetUpdateBasicDtl extends TradeRequest
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
    #jflkdj
    public function rules()
    {
        return [
            "initialBusinessDetails.id"=>"required|digits_between:1,9223372036854775807"
        ];
    }   
}
<?php

namespace App\Http\Requests\Trade;


class ApplicationId extends TradeRequest
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
        return [
            'applicationId'    => 'required|digits_between:1,9223372036854775807'
        ];
    }
}

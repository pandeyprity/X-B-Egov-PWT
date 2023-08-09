<?php

namespace App\Http\Requests\DashboardRequest;

use Illuminate\Foundation\Http\FormRequest;

class RequestCollectionPercentage extends FormRequest
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

        $rules['month'] = "nullable|in:1,2,3,4,5,6,7,8,9,10,11,12";
        $rules['year'] = "nullable|";
        return $rules;
    }
}

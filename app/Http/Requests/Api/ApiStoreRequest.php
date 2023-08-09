<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Validate\ValidateTrait;

class ApiStoreRequest extends FormRequest
{
    use ValidateTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->a();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'endPoint' => ['required'],
            'description' => ['required']
        ];
    }
}

<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\Validate\ValidateTrait;

/**
 * Created On-25-06-2022 
 * Created By-Anshu Kumar
 * 
 * Purpose- Validating User while Log In 
 * 
 * Code Tested By-
 * Code Tested Date-
 */
class LoginUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => [
                'required'
            ],
            'type' => "nullable|in:mobile"
        ];
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Contracts\Validation\Validator;
     * @param  \Illuminate\Contracts\Validation\Validator;  $validator
     * @return Illuminate\Http\Exceptions\HttpResponseException
     */

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}

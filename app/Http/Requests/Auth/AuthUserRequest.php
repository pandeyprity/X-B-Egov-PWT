<?php

namespace App\Http\Requests\Auth;

use App\Traits\Validate\ValidateTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Created On:-25-06-2022 
 * Created By:-Anshu 
 * 
 * Purpose:-For validate the User While Registering the User
 * 
 * Code Tested By
 * Date-
 * Tested By-
 * 
 */
class AuthUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ],
            'mobile' => ['required', 'min:10','max:10'],
            'ulb' => ['required', 'integer'],
            'userType' => ['required']
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

<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Validate\ValidateTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Created By-Anshu Kumar
 * Created On-27-06-2022 
 * 
 * Purpose-Password Validation while Changing Password
 * 
 * Code Tested By
 * 
 */

class ChangePassRequest extends FormRequest
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
            'password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ],
            'newPassword'=> [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ]
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

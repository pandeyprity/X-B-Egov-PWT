<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class CustomRequestException extends Exception
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function __construct($validator)
    {
        parent::__construct('The given data was invalid.');

        $this->validator = $validator;
    }

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors()
    {
        return $this->validator->errors()->messages();
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        if ($request->acceptsJson()) {
            $errors = [
                'status' => false,
                'message' => 'The given data was invalid',
                'errors' => $this->errors(),
            ];

            return response()->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}

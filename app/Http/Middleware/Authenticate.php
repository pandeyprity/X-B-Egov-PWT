<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */

    /*
        protected function redirectTo expects a Path and we don't need path
        * so this function has commented 
     */

    // protected function redirectTo($request)
    // {
    //     if (!$request->expectsJson()) {
    //         // return route('api/login');
    //         return response()->json(['error' => 'Unauthenticated.'], 401);
    //     }
    // }

    /*
        * this function used instead of redirect function
    */
    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json(
            [
                'status' => true,
                'authenticated' => false
            ]
        ));
    }
}

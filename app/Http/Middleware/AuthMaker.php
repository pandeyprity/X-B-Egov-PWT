<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthMaker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!Auth()->user() && $request->auth)
        {
            switch($request->currentAccessToken["tokenable_type"])
            {
                case "App\\Models\\Auth\\User": Auth::login(new \App\Models\User($request->auth));
                                                Auth()->user()->ulb_id=$request->auth["ulb_id"];
                                                break;
                default                       : Auth::login(new \App\Models\ActiveCitizen($request->auth));
                                                break;
            }
            collect($request->auth)->map(function($val,$key){
                Auth()->user()->$key = $val;
            });
            Auth()->user()->id=$request->auth["id"];
        }
        return $next($request);
    }
}

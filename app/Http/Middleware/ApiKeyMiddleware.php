<?php

namespace App\Http\Middleware;

use App\Models\Auth\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = Config::get('module-constants.API_KEY');
        // Returns boolean
        if ($request->headers->has('API-KEY') == false) {
            return response()->json([
                'status' => false,
                'message' => 'No Authorization Key',
            ], 400);
        };
        // Returns header value with default as fallback
        $val = $request->header('API-KEY', 'default_value');
        if ($val === $apiKey) {
            $this->validateApiKey($request);
            return $next($request);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid API Key',
            ], 400);
        }
    }

    // Api Token Validity
    public function validateApiKey($request)
    {
        $apiToken = $request->apiToken;
        if (isset($apiToken)) {
            $mPersonalAccessToken = new PersonalAccessToken();
            $tokenValidity = $mPersonalAccessToken->findToken($apiToken);
            if (collect($tokenValidity)->isEmpty())
                return responseMsgs(false, "Api Token Is Invalid", []);
        }
    }
}

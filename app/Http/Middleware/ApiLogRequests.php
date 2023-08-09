<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Created On-02-07-2022 
 * Created By-Anshu Kumar
 * ----------------------------------------------------------------------------------------------------
 * Middleware Creates logs of the various api responses and request, their start time, end time 
 * 
 */
class ApiLogRequests
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
        $request->start = microtime(true);
        return $next($request);
    }

    /**
     * Handle an response time.
     * 
     * @param Illuminate\Http\Request $request
     * @return log function 
     */
    public function terminate($request, $response)
    {
        $request->end = microtime(true);
        $this->log($request, $response);
    }
    /**
     * Create Log in storage/logs/laravel.log 
     */

    protected function log($request, $response)
    {
        $duration = $request->end - $request->start;
        $url = $request->fullUrl();
        $method = $request->getMethod();
        $ip = $request->getClientIp();
        $log = "{$ip}: {$method}@{$url} - {$duration}ms \n" .
            "StartTime: {$request->start} \n" .
            "EndTime: {$request->end} \n" .
            "Request : {[$request->all()]} \n" .
            "Response : {$response->getContent()} \n";
        // Log::info($log);
        Log::channel('apilogs')->info($log);
    }
}

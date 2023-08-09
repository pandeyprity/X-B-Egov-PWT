<?php

namespace App\Pipelines;

use Closure;

/**
 * | Created On-10-03-2023 
 * | Created By-Mrinal Kumar
 */
class SearchHolding
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('holdingNo')) {
            return $next($request);
        }
        return $next($request)
            ->orderBy('id')
            ->where('holding_no', request()->input('holdingNo'))
            ->orWhere('new_holding_no', request()->input('holdingNo'));
    }
}

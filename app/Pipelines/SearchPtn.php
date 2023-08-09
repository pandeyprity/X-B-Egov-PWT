<?php

namespace App\Pipelines;

use Closure;

class SearchPtn
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('ptNo')) {
            return $next($request);
        }
        return $next($request)
            ->where('pt_no', "ilike", request()->input('ptNo'));
    }
}

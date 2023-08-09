<?php

namespace App\Pipelines\SafInbox;

use Closure;


class SearchByApplicationNo
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('applicationNo')) {
            return $next($request);
        }
        return $next($request)
            ->where('saf_no', 'ilike', '%' . request()->input('applicationNo') . '%');
    }
}

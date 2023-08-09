<?php

namespace App\Pipelines\SafInbox;

use Closure;


class SearchByMobileNo
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('mobileNo')) {
            return $next($request);
        }
        return $next($request)
            ->where('o.mobile_no', 'ilike', '%' . request()->input('mobileNo') . '%');
    }
}

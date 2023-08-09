<?php

namespace App\Pipelines\GbSafInbox;

use Closure;


class GbSafByMobileNo
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('mobileNo')) {
            return $next($request);
        }
        return $next($request)
            ->where('gbo.mobile_no', 'ilike', '%' . request()->input('mobileNo') . '%');
    }
}

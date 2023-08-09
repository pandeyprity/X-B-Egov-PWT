<?php

namespace App\Pipelines\GbSafInbox;

use Closure;


class GbSafByApplicationNo
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

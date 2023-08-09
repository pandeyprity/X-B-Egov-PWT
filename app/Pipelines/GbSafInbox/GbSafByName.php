<?php

namespace App\Pipelines\GbSafInbox;

use Closure;

class GbSafByName
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('name')) {
            return $next($request);
        }
        return $next($request)
            ->where('gbo.officer_name', 'ilike', '%' . request()->input('name') . '%');
    }
}

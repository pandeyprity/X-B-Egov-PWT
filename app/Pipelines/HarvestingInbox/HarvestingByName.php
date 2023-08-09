<?php

namespace App\Pipelines\HarvestingInbox;

use Closure;

class HarvestingByName
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('name')) {
            return $next($request);
        }
        return $next($request)
            ->where('owner_name', 'ilike', '%' . request()->input('name') . '%');
    }
}

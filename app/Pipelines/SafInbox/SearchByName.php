<?php

namespace App\Pipelines\SafInbox;

use Closure;


class SearchByName
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('name')) {
            return $next($request);
        }
        return $next($request)
            ->where('o.owner_name', 'ilike', '%' . request()->input('name') . '%');
    }
}

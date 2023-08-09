<?php

namespace App\Pipelines\ObjectionInbox;

use Closure;

class ObjectionByName
{

    public function handle($request, Closure $next)
    {
        if (!request()->has('name')) {
            return $next($request);
        }

        return $next($request)
            ->where('prop_owners.owner_name', 'ilike', '%' . request()->input('name') . '%');
    }
}

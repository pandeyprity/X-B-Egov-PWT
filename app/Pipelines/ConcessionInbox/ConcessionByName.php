<?php

namespace App\Pipelines\ConcessionInbox;

use Closure;

class ConcessionByName
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('name')) {
            return $next($request);
        }
        return $next($request)
            ->where('prop_active_concessions.applicant_name', 'ilike', '%' . request()->input('name') . '%');
    }
}

<?php

namespace App\Pipelines\ObjectionInbox;

use Closure;

class ObjectionByApplicationNo
{

    public function handle($request, Closure $next)
    {
        if (!request()->has('applicationNo')) {
            return $next($request);
        }

        return $next($request)
            ->where('objection_no', 'ilike', '%' . request()->input('applicationNo') . '%');
    }
}

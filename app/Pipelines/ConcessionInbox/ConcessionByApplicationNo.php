<?php

namespace App\Pipelines\ConcessionInbox;

use Closure;

class ConcessionByApplicationNo
{

    public function handle($request, Closure $next)
    {
        if (!request()->has('applicationNo')) {
            return $next($request);
        }
        return $next($request)
            ->where('application_no', 'ilike', '%' . request()->input('applicationNo') . '%');
    }
}

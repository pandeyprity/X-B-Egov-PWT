<?php

namespace App\Pipelines;

use Closure;

/**
 * | Created On-06-03-2023 
 * | Created By-Anshu Kumar
 * | Created for the Property Module Permissions by Role
 */
class ModulePermissions
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('module')) {
            return $next($request);
        }

        return $next($request)
            ->where('action_masters.module_id', request()->input('module'));
    }
}

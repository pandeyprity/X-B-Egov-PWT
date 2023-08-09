<?php

namespace App\Pipelines;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class WaterInbox
{
    public function handle($request, Closure $next)
    {
        $waterMstrId = Config::get('workflow-constants.WATER_MASTER_ID');
        $baseUrl = Config::get('workflow-constants.baseUrl');

        if (!request()->input('wf_mstr_id', $waterMstrId)) {
            return $next($request);
        }

        return $response = Http::withHeaders(['0']);


        //     'Authorization' => 'Bearer ' . request()->input('bearerToken'),
        //     'Accept' => 'application/json'
        // ])->post('http://127.0.0.1:8000/api/water/inbox');
    }
}

<?php

namespace App\Repository\Water\Interfaces;

use Illuminate\Http\Request;

interface IConsumer
{
    public function calConsumerDemand(Request $request);
    public function getconsumerRelatedData($applicatioId);
}

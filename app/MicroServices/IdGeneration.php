<?php

namespace App\MicroServices;

use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Masters\IdGenerationParam;
use App\Models\UlbMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * | Created On-16-01-2023 
 * | Created By-Anshu Kumar
 * | Created for Id Generation MicroService
 */
class IdGeneration
{
    /**
     * | Generate Transaction ID
     */
    public function generateTransactionNo($ulbId)
    {
        $tranParamId = Config::get("waterConstaint.PARAM_IDS");
        $idGeneration = new PrefixIdGenerator($tranParamId['TRN'], $ulbId);
        $transactionNo = $idGeneration->generate();
        $transactionNo = str_replace('/', '-', $transactionNo);
        return $transactionNo;
    }

    /**
     * | Generate Random OTP 
     */
    public function generateOtp()
    {
        // $otp = Carbon::createFromDate()->milli . random_int(100, 999);
        $otp = 123123;
        return $otp;
    }
}

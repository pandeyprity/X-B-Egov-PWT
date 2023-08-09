<?php

namespace App\Pipelines\CareTakers;

use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Trade\TradeLicence;
use App\Models\Trade\TradeOwner;
use Carbon\Carbon;
use Closure;
use Exception;

/**
 * | Created On-21-04-2023 
 * | Created By-Anshu Kumar
 * | Status - Closed
 * | ------------------------------
 * | PipeLine Class to Tag Trade 
 */
class TagTrade
{
    private $_mTradeLicenses;
    private $_mTradeOwners;
    private $_currentDate;
    private $_tradeId;
    private $_mActiveCitizenUnderCares;
    private $_licenseNo;

    /**
     * | Initialing Values
     */
    public function __construct()
    {
        $this->_mTradeLicenses = new TradeLicence();
        $this->_mTradeOwners = new TradeOwner();
        $this->_currentDate = Carbon::now();
        $this->_mActiveCitizenUnderCares = new ActiveCitizenUndercare();
    }


    public function handle($request, Closure $next)
    {

        if (request()->input('moduleId') != 3) {
            return $next($request);
        }

        $referenceNo = request()->input('referenceNo');
        $trade = $this->_mTradeLicenses->getTradeIdByLicenseNo($referenceNo);
        if (!$trade)
            throw new Exception('Enter Valid License No.');
        $this->_tradeId = $trade->id;
        $this->_licenseNo = $referenceNo;
        $this->isTradeAlreadyTagged();           // function (1.1)
        $tradeOwner = $this->_mTradeOwners->getFirstOwner($this->_tradeId);
        $underCareReq = [
            'license_id' => $this->_licenseNo,
            'date_of_attachment' => $this->_currentDate,
            'mobile_no' => $tradeOwner->mobile_no ?? null,
            'citizen_id' => auth()->user()->id
        ];
        $this->_mActiveCitizenUnderCares->store($underCareReq);
        return "License Successfully Tagged";
    }

    /**
     * | Is The Property Already Tagged
     */
    public function isTradeAlreadyTagged()
    {
        $taggedTradeList = $this->_mActiveCitizenUnderCares->getTaggedTrades($this->_licenseNo);
        $totalTrades = $taggedTradeList->count('license_no');
        if ($totalTrades > 3)                                               // Check if the Property is already tagged 3 times of not
            throw new Exception("License has already tagged 3 Times");

        $citizens = $taggedTradeList->pluck('citizen_id');

        if ($citizens->contains(auth()->user()->id))                                // Check Is the Property already tagged by the citizen 
            throw new Exception("License Already Tagged");
    }
}

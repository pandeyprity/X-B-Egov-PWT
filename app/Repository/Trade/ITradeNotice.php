<?php

namespace App\Repository\Trade;

use Illuminate\Http\Request;

/**
 * | Created On-09-02-2023 
 * | Created By-Sandeep Bara
 * ------------------------------------------------------------------------------------------
 * | Interface for Eloquent Notice
 */

interface ITradeNotice
{
    public function addDenail(Request $request);
    public function inbox(Request $request);
    public function outbox(Request $request);
    public function specialInbox(Request $request);
    public function btcInbox(Request $request);
    public function denialView(Request $request);
    public function approveReject(Request $request);
}
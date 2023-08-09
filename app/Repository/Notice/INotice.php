<?php

namespace App\Repository\Notice;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Created By Sandeep Bara
 * Date 2023-03-027
 * Notice Module
 */

 interface INotice
{
    function add(Request $request);
    public function noticeList(Request $request);
    public function noticeView(Request $request);
    public function fullDtlById(Request $request);
    public function inbox(Request $request);
    public function outbox(Request $request);
    public function approveReject(Request $request);
    public function openNoticiList($sedule=false);
    public function genrateAndSendNotice(Request $request);
}

<?php

namespace App\Repository\Trade;

use Illuminate\Http\Request;

interface IReport
{
    public function CollectionReports(Request $request);
    public function teamSummary (Request $request);
    public function valideAndExpired(Request $request);
    public function CollectionSummary(Request $request);
    public function tradeDaseboard(Request $request);
    public function applicationTypeCollection(Request $request);
    public function userAppliedApplication(Request $request);
    public function collectionPerfomance(Request $request);
    public function ApplicantionTrackStatus(Request $request);
    public function applicationAgentNotice(Request $request);
    public function noticeSummary(Request $request);
    public function levelwisependingform(Request $request);
    public function levelUserPending(Request $request);
    public function userWiseWardWiseLevelPending(Request $request);
    public function levelformdetail(Request $request);
    public function bulkPaymentRecipt(Request $request);
    public function applicationStatus(Request $request);

}
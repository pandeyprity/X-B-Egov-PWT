<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-19-11-2022 
 * | Created By-Sandeep Bara
 **/

interface IPropertyDeactivate
{
   public function readHoldigbyNo(Request $request); 
   public function deactivatProperty(Request $request);
   public function inbox(Request $request);
   public function outbox(Request $request);
   public function specialInbox(Request $request);
   public function readDeactivationReq(Request $request);
   public function readDocumentPath($path);
}

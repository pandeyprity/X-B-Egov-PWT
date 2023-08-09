<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use Exception;
use Illuminate\Http\Request;

/**
 * | Created On-29-03-2023
 * | Modified By-Mrinal Kumar
 * | Created for Id Generation
 */

class IdGeneratorController extends Controller
{
    public function idGenerator(Request $req)
    {
        try {
            $idGeneration = new PrefixIdGenerator($req->paramId, $req->ulbId);
            $id = $idGeneration->generate();

            return responseMsgs(true, "Your Id!", $id, "", "", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}

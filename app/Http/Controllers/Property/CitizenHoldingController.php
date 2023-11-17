<?php

namespace App\Http\Controllers\Property;

use App\Repository\Property\Interfaces\iSafRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class CitizenHoldingController extends Controller
{
    private $_HoldingTaxController;
    public function __construct()
    {
        $this->_HoldingTaxController = App::makeWith(HoldingTaxController::class,["iSafRepository"=>app(iSafRepository::class)]);  
    }

    public function getHoldingDues(Request $request)
    {
        return $this->_HoldingTaxController->getHoldingDues($request);
    }

    public function ICICPaymentRequest(Request $request)
    {
        // $validater = Vali
        // $request->validate(
        //     [                  
        //         "propId" => "required|digits_between:1,9223372036854775807",
        //         'paidAmount' => 'nullable|required_if:paymentType,==,isPartPayment|integer',
        //     ]
        // );
    }
}

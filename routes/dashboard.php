<?php

use App\Http\Controllers\Dashboard\DistrictWiseDataController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\JskController;
use App\Http\Controllers\Dashboard\StateDashboardController;

/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * | Jsk Controller
     Serial No : 01
     */


    /**
     * | State Dashboard
     */
    Route::controller(StateDashboardController::class)->group(function () {
        Route::post('state/ulb-wise-collection', 'ulbWiseCollection');              // 01
        Route::post('state/count-online-payment', 'onlinePaymentCount');
        Route::post('state/ulb-wise-data', 'ulbWiseData');
        Route::post('state/collection-percentage', 'stateWiseCollectionPercentage');
        Route::post('state/district-wise-data', 'districtWiseData');

        Route::post('state/property/DCB', 'stateDashboardDCB');                    //done
    });

    /**
     * | District Wise Data (03)
     */
    Route::controller(DistrictWiseDataController::class)->group(function () {
        Route::post('district/district-wise-collection', 'districtWiseCollection'); //
    });
});

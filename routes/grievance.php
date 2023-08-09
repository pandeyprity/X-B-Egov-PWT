<?php

use App\Http\Controllers\Grievance\GrievaceController;
use Illuminate\Support\Facades\Route;

/**
     * | Created On-23-11-2022 
     * | Created By-Sam kerketta
     * | Grievamne API 
     */
// Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

# Grievamne API list basic 
Route::controller(GrievaceController::class)->group(function () {
    Route::post('postFileComplaint', 'postFileComplain');
    Route::post('getAllComplainById', 'getAllComplainById');
    Route::post('updateRateComplaintById/{id}', 'updateRateComplaintById');
    Route::get('getAllComplaintList/{id?}', 'getAllComplaintList');
    Route::post('putReopenComplaintById/{id?}', 'putReopenComplaintById');
});
// });
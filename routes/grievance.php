<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GrievanceController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Created By : 
| Created At :
| Modified By : Sam Kerketta
| Modefied At : 12-08-2023
*/

/**
 * | uc : Under Construction
 * | w  : Working
 * | r  : Remove
 * | nu : Working api but Not Used/maybe used in future
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(GrievanceController::class)->group(function () {
    Route::get('ping', 'pong');                                                         // r
    # Citizen and Agency                                                           
    Route::post('register-grievance', 'registerGrievance');                             // w 
    Route::post('reg/register-grievance', 'registerGrievance');
    Route::post('auth/req-otp', 'requestOtp');                                          // w / unauth
    Route::post('auth/verify-otp', 'verifyOtp');                                        // w / unauth
    Route::post('auth/get-grievance', 'getAppliedGrievance');                           // w / unauth
    Route::post('list-applied-grievance', 'getGrievanceForAgency');                     // w
    Route::post('get-application-by-id', "getGrievanceById");                           // uc
    Route::post('rejected-application-by-agency', 'rejectedGrievanceByAgency');         // w
    Route::post('search-grievance-for-agency', 'searchGrievanceForAgency');             // w
    Route::post('auth/get-grievance-by-mobileno', 'getGrievanceByMobileNo');            // uc / unauth
    Route::post('auth/view-grievance-full-details', 'viewGrievanceDetails');            // uc / unauth
    Route::post('get-wf-solved-grievance', 'getWfApprovedGrievances');                  // w
    Route::post('get-wf-rejected-grievance', 'getWfRejectedGrievances');                // uc
    Route::post('view-grievance-full-details', 'viewGrievanceFullDetails');             // w
    Route::post('close-grievance-by-agency', 'agencyFinalCloser');                      // uc
    Route::post('agency-reopen-grievance', 'grievanceReopen');                          // uc
    Route::post('update-citzen-grievance', 'updateCitizenGrievance');                   // uc

    # Parent workflow api 
    Route::post('wf/send-application-to-wf', 'sendApplicationToWf');                    // uc
    Route::post('wf/get-details-by-id', 'getDetailsById');                              // w  
    Route::post('wf/inbox', 'inbox');                                                   // w
    Route::post('wf/outbox', 'outbox');                                                 // w
    Route::post('wf/special-inbox', 'specialInbox');                                    // w
    Route::post('get-doc-list', 'getDocToUpload');                                      // w
    Route::post('get-uploaded-doc', 'listUploadedDocs');                                // w
    Route::post('wf/verify-reject-doc', 'verifyRejectDocs');                            // w                            
    Route::post('wf/post-next-level', 'postNextLevel');                                 // w
    Route::post('wf/approve-reject-applications', 'finalApprovalRejection');            // w
    Route::post('wf/post-associated-wf', 'postAssociatedWf');                           // uc
    Route::post('wf/back-to-parent-wf', 'sendApplicationToParentWf');                   // uc

    # Associated workflow
    Route::post('awf/post-next-level', 'awfPostNextLevel');                             // uc
    Route::post('awf/verify-reject-doc', 'awfVerifyRejectDocs');                        // uc
    Route::post('awf/approve-reject-applications', 'approvalRejectionAssociatedWf');    // uc       
});

@ -0,0 +1,215 @@
<?php

use App\Http\Controllers\Bandobastee\BandobasteeController;
use App\Http\Controllers\Markets\BanquetMarriageHallController;
use App\Http\Controllers\Markets\DharamshalaController;
use App\Http\Controllers\Markets\HostelController;
use App\Http\Controllers\Markets\LodgeController;
use Illuminate\Support\Facades\Route;

/**
 * | Created On - 12 Aug 2023
 * | Created By - Bikash Kumar
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * | Lodge Controller
     * | Controller-07
     * | By - Bikash Kumar
     * | Date - 12 Aug 2023
     * | Status - Open
     */
    Route::controller(LodgeController::class)->group(function () {
        Route::post('lodge/add-new', 'addNew');                                                                      // 01   ( Save Application )  
        Route::post('lodge/list-inbox', 'listInbox');                                                                // 02 ( Application Inbox Lists )
        Route::post('lodge/list-outbox', 'listOutbox');                                                              // 03 ( Application Outbox Lists )
        Route::post('lodge/get-details-by-id', 'getDetailsById');                                                    // 04 ( Get Application Details By Application ID )
        Route::post('lodge/list-applied-applications', 'listAppliedApplications');                                   // 05 ( Get Applied Applications List )
        Route::post('lodge/escalate-application', 'escalateApplication');                                            // 06 ( Escalate or De-escalate Application )
        Route::post('lodge/list-escalated', 'listEscalated');                                                        // 07 ( Special Inbox Applications )
        Route::post('lodge/forward-next-level', 'forwardNextLevel');                                                 // 08 ( Forward or Backward Application )
        Route::post('lodge/comment-application', 'commentApplication');                                              // 09 ( Independent Comment )
        Route::post('lodge/view-lodge-documents', 'viewLodgeDocuments');                                             // 10 ( Get Uploaded Document By Application ID )
        Route::post('lodge/view-active-document', 'viewActiveDocument');                                             // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('lodge/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                                  // 12 ( Get Uploaded Document By Application ID )
        Route::post('lodge/approved-or-reject', 'approvedOrReject');                                                 // 13 ( Approve or Reject )
        Route::post('lodge/list-approved', 'listApproved');                                                          // 14 ( Approved list for Citizen)
        Route::post('lodge/list-rejected', 'listRejected');                                                          // 15 ( Rejected list for Citizen)
        Route::post('lodge/generate-payment-order-id', 'generatePaymentOrderId');                                    // 16 ( Generate Payment Order ID)
        Route::post('lodge/get-application-details-for-payment', 'getApplicationDetailsForPayment');                 // 17 ( Application Details For Payments )
        Route::post('lodge/verify-or-reject-doc', 'verifyOrRejectDoc');                                              // 18 ( Application Details For Payments )
        Route::post('lodge/back-to-citizen', 'backToCitizen');                                                       // 19 ( Application Details For Payments )
        Route::post('lodge/list-btc-inbox', 'listBtcInbox');                                                         // 20 ( Application Details For Payments )
        Route::post('lodge/reupload-document', 'reuploadDocument');                                                  // 21 ( Application Details For Payments )
        Route::post('lodge/payment-by-cash', 'paymentByCash');                                                       // 22 ( Application Details For Payments )
        Route::post('lodge/entry-cheque-dd', 'entryChequeDd');                                                       // 23 ( Application Details For Payments )
        Route::post('lodge/clear-or-bounce-cheque', 'clearOrBounceCheque');                                          // 24 ( Application Details For Payments )
        Route::post('lodge/get-renew-application-details', 'getApplicationDetailsForRenew');                         // 25 ( Application Details For Payments )
        Route::post('lodge/renew-application', 'renewApplication');                                                  // 26 ( Application Details For Payments )
        Route::post('lodge/get-application-details-for-edit', 'getApplicationDetailsForEdit');                       // 27 ( View Application Details For Edit )
        Route::post('lodge/edit-application', 'editApplication');                                                    // 28 ( Edit Applications ) 
        Route::post('lodge/get-application-between-date', 'getApplicationBetweenDate');                              // 29 ( Get Application Between two date )
        Route::post('lodge/get-application-financial-year-wise', 'getApplicationFinancialYearWise');                 // 30 ( Get Application Financial Year Wise )
        Route::post('lodge/payment-collection', 'paymentCollection');                                                // 31 ( Get Application Financial Year Wise )
        Route::post('lodge/rule-wise-applications', 'ruleWiseApplications');                                         // 32 ( Get Application Rule Wise )
        Route::post('lodge/get-application-by-lodge-type', 'getApplicationByLodgelType');                            // 33 ( Get Application hostel type Wise )
    });

    /**
     * | Banquet Marriage Hall Controller
     * | Controller-08
     * | By - Bikash Kumar
     * | Date - 12 Aug 2023
     * | Status - Open
     */
    Route::controller(BanquetMarriageHallController::class)->group(function () {
        Route::post('bm-hall/add-new', 'addNew');                                                                    // 01   ( Save Application )  
        Route::post('bm-hall/list-inbox', 'listInbox');                                                              // 02 ( Application Inbox Lists )
        Route::post('bm-hall/list-outbox', 'listOutbox');                                                            // 03 ( Application Outbox Lists )
        Route::post('bm-hall/get-details-by-id', 'getDetailsById');                                                  // 04 ( Get Application Details By Application ID )
        Route::post('bm-hall/list-applied-applications', 'listAppliedApplications');                                 // 05 ( Get Applied Applications List )
        Route::post('bm-hall/escalate-application', 'escalateApplication');                                          // 06 ( Escalate or De-escalate Application )
        Route::post('bm-hall/list-escalated', 'listEscalated');                                                      // 07 ( Special Inbox Applications )
        Route::post('bm-hall/forward-next-level', 'forwardNextLevel');                                               // 08 ( Forward or Backward Application )
        Route::post('bm-hall/comment-application', 'commentApplication');                                            // 09 ( Independent Comment )
        Route::post('bm-hall/view-bm-hall-documents', 'viewBmHallDocuments');                                        // 10 ( Get Uploaded Document By Application ID )
        Route::post('bm-hall/view-active-document', 'viewActiveDocument');                                           // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('bm-hall/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                                // 12 ( Get Uploaded Document By Application ID )
        Route::post('bm-hall/approved-or-reject', 'approvedOrReject');                                               // 13 ( Approve or Reject )
        Route::post('bm-hall/list-approved', 'listApproved');                                                        // 14 ( Approved list for Citizen)
        Route::post('bm-hall/list-rejected', 'listRejected');                                                        // 15 ( Rejected list for Citizen)
        Route::post('bm-hall/generate-payment-order-id', 'generatePaymentOrderId');                                  // 16 ( Generate Payment Order ID)
        Route::post('bm-hall/get-application-details-for-payment', 'getApplicationDetailsForPayment');               // 17 ( Application Details For Payments )
        Route::post('bm-hall/verify-or-reject-doc', 'verifyOrRejectDoc');                                            // 18 ( Verify or Reject Documents )
        Route::post('bm-hall/back-to-citizen', 'backToCitizen');                                                     // 19 ( Application Back to Citizen )
        Route::post('bm-hall/list-btc-inbox', 'listBtcInbox');                                                       // 20 ( List Application Back to Citizen )
        Route::post('bm-hall/reupload-document', 'reuploadDocument');                                                // 21 ( Reupload Document for Pending Documents)
        Route::post('bm-hall/payment-by-cash', 'paymentByCash');                                                     // 22 ( Cash Payments )
        Route::post('bm-hall/entry-cheque-dd', 'entryChequeDd');                                                     // 23 ( Entry Cheque or DD For Payments )
        Route::post('bm-hall/clear-or-bounce-cheque', 'clearOrBounceCheque');                                        // 24 (Clear or Bouns Cheque For Payments )
        Route::post('bm-hall/get-renew-application-details', 'getApplicationDetailsForRenew');                       // 25 ( Get Application Details For Renew )
        Route::post('bm-hall/renew-application', 'renewApplication');                                                // 26 ( Renew Applications )
        Route::post('bm-hall/get-application-details-for-edit', 'getApplicationDetailsForEdit');                     // 27 ( View Application Details For Edit )
        Route::post('bm-hall/edit-application', 'editApplication');                                                  // 28 ( Edit Applications )
        Route::post('bm-hall/get-application-between-date', 'getApplicationBetweenDate');                            // 29 ( Get Application Between two date )
        Route::post('bm-hall/get-application-financial-year-wise', 'getApplicationFinancialYearWise');               // 30 ( Get Application Financial Year Wise )
        Route::post('bm-hall/payment-collection', 'paymentCollection');                                              // 31 ( Get Application Financial Year Wise )
        Route::post('bm-hall/rule-wise-applications', 'ruleWiseApplications');                                       // 32 ( Get Application Rule Wise )
        Route::post('bm-hall/get-application-by-hall-type', 'getApplicationByHallType');                             // 32 ( Get Application Rule Wise )
        Route::post('bm-hall/get-application-by-organization-type', 'getApplicationByOrganizationType');             // 33 ( Get Application organization type Wise )
    });


    /**
     * | Hostel Controller
     * | Controller-09
     * | By - Bikash Kumar
     * | Date - 12 Aug 2023
     * | Status - Open 
     */
    Route::controller(HostelController::class)->group(function () {
        Route::post('hostel/add-new', 'addNew');                                                                     // 01   ( Save Application )  
        Route::post('hostel/list-inbox', 'listInbox');                                                               // 02 ( Application Inbox Lists )
        Route::post('hostel/list-outbox', 'listOutbox');                                                             // 03 ( Application Outbox Lists )
        Route::post('hostel/get-details-by-id', 'getDetailsById');                                                   // 04 ( Get Application Details By Application ID )
        Route::post('hostel/list-applied-applications', 'listAppliedApplications');                                  // 05 ( Get Applied Applications List )
        Route::post('hostel/escalate-application', 'escalateApplication');                                           // 06 ( Escalate or De-escalate Application )
        Route::post('hostel/list-escalated', 'listEscalated');                                                       // 07 ( Special Inbox Applications )
        Route::post('hostel/forward-next-level', 'forwardNextLevel');                                                // 08 ( Forward or Backward Application )
        Route::post('hostel/comment-application', 'commentApplication');                                             // 09 ( Independent Comment )
        Route::post('hostel/view-hostel-documents', 'viewHostelDocuments');                                          // 10 ( Get Uploaded Document By Application ID )
        Route::post('hostel/view-active-document', 'viewActiveDocument');                                            // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('hostel/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                                 // 12 ( Get Uploaded Document By Application ID )
        Route::post('hostel/approved-or-reject', 'approvedOrReject');                                                // 13 ( Approve or Reject )
        Route::post('hostel/list-approved', 'listApproved');                                                         // 14 ( Approved list for Citizen)
        Route::post('hostel/list-rejected', 'listRejected');                                                         // 15 ( Rejected list for Citizen)
        Route::post('hostel/generate-payment-order-id', 'generatePaymentOrderId');                                   // 16 ( Generate Payment Order ID)
        Route::post('hostel/get-application-details-for-payment', 'getApplicationDetailsForPayment');                // 17 ( Application Details For Payments )
        Route::post('hostel/verify-or-reject-doc', 'verifyOrRejectDoc');                                             // 18 ( Application Details For Payments )
        Route::post('hostel/back-to-citizen', 'backToCitizen');                                                      // 19 ( Application Details For Payments )
        Route::post('hostel/list-btc-inbox', 'listBtcInbox');                                                        // 20 ( Application Details For Payments )
        Route::post('hostel/reupload-document', 'reuploadDocument');                                                 // 21 ( Application Details For Payments )
        Route::post('hostel/payment-by-cash', 'paymentByCash');                                                      // 22 ( Application Details For Payments )
        Route::post('hostel/entry-cheque-dd', 'entryChequeDd');                                                      // 23 ( Application Details For Payments )
        Route::post('hostel/clear-or-bounce-cheque', 'clearOrBounceCheque');                                         // 24 ( Application Details For Payments 
        Route::post('hostel/get-renew-application-details', 'getApplicationDetailsForRenew');                        // 25 ( Application Details For Payments )
        Route::post('hostel/renew-application', 'renewApplication');                                                 // 26 ( Application Details For Payments )
        Route::post('hostel/get-application-details-for-edit', 'getApplicationDetailsForEdit');                      // 27 ( View Application Details For Edit )
        Route::post('hostel/edit-application', 'editApplication');                                                   // 28 ( Edit Applications )
        Route::post('hostel/get-application-between-date', 'getApplicationBetweenDate');                             // 29 ( Get Application Between two date )
        Route::post('hostel/get-application-financial-year-wise', 'getApplicationFinancialYearWise');                // 30 ( Get Application Financial Year Wise )
        Route::post('hostel/payment-collection', 'paymentCollection');                                               // 31 ( Get Application Financial Year Wise )
        Route::post('hostel/rule-wise-applications', 'ruleWiseApplications');                                        // 32 ( Get Application Rule Wise )
        Route::post('hostel/get-application-by-hostel-type', 'getApplicationByHostelType');                          // 33 ( Get Application Hostel type Wise )
    });

    /**
     * | Dharamshala Controller
     * | Controller-10
     * | By - Bikash Kumar
     * | Date - 12 Aug 2023
     * | Status - Open 
     */
    Route::controller(DharamshalaController::class)->group(function () {
        Route::post('dharamshala/add-new', 'addNew');                                                                // 01   ( Save Application )  
        Route::post('dharamshala/list-inbox', 'listInbox');                                                          // 02 ( Application Inbox Lists )
        Route::post('dharamshala/list-outbox', 'listOutbox');                                                        // 03 ( Application Outbox Lists )
        Route::post('dharamshala/get-details-by-id', 'getDetailsById');                                              // 04 ( Get Application Details By Application ID )
        Route::post('dharamshala/list-applied-applications', 'listAppliedApplications');                             // 05 ( Get Applied Applications List )
        Route::post('dharamshala/escalate-application', 'escalateApplication');                                      // 06 ( Escalate or De-escalate Application )
        Route::post('dharamshala/list-escalated', 'listEscalated');                                                  // 07 ( Special Inbox Applications )
        Route::post('dharamshala/forward-next-level', 'forwardNextLevel');                                           // 08 ( Forward or Backward Application )
        Route::post('dharamshala/comment-application', 'commentApplication');                                        // 09 ( Independent Comment )
        Route::post('dharamshala/view-dharamshala-documents', 'viewDharamshalaDocuments');                           // 10 ( Get Uploaded Document By Application ID )
        Route::post('dharamshala/view-active-document', 'viewActiveDocument');                                       // 11 ( Get Uploaded Document By Advertisement ID )
        Route::post('dharamshala/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                            // 12 ( Get Uploaded Document By Application ID )
        Route::post('dharamshala/approved-or-reject', 'approvedOrReject');                                           // 13 ( Approve or Reject )
        Route::post('dharamshala/list-approved', 'listApproved');                                                    // 14 ( Approved list for Citizen)
        Route::post('dharamshala/list-rejected', 'listRejected');                                                    // 15 ( Rejected list for Citizen)
        Route::post('dharamshala/generate-payment-order-id', 'generatePaymentOrderId');                              // 16 ( Generate Payment Order ID)
        Route::post('dharamshala/get-application-details-for-payment', 'getApplicationDetailsForPayment');           // 17 ( Application Details For Payments )
        Route::post('dharamshala/verify-or-reject-doc', 'verifyOrRejectDoc');                                        // 18 ( Verify or Reject Documents )
        Route::post('dharamshala/back-to-citizen', 'backToCitizen');                                                 // 19 ( Application Back to Citizen )
        Route::post('dharamshala/list-btc-inbox', 'listBtcInbox');                                                   // 20 ( List Application Back to Citizen )
        Route::post('dharamshala/reupload-document', 'reuploadDocument');                                            // 21 ( Reupload Documents For Pending Documents )
        Route::post('dharamshala/payment-by-cash', 'paymentByCash');                                                 // 22 ( Payment via Cash )
        Route::post('dharamshala/entry-cheque-dd', 'entryChequeDd');                                                 // 23 ( Entry Cheque or DD For Payments )
        Route::post('dharamshala/clear-or-bounce-cheque', 'clearOrBounceCheque');                                    // 24 (Clear or Bouns Cheque For Payments )
        Route::post('dharamshala/get-renew-application-details', 'getApplicationDetailsForRenew');                   // 25 ( Application Details For Renew )
        Route::post('dharamshala/renew-application', 'renewApplication');                                            // 26 ( Renew Application )
        Route::post('dharamshala/get-application-details-for-edit', 'getApplicationDetailsForEdit');                 // 27 ( View Application Details For Edit )
        Route::post('dharamshala/edit-application', 'editApplication');                                              // 28 ( Edit Applications )
        Route::post('dharamshala/get-application-between-date', 'getApplicationBetweenDate');                        // 29 ( Get Application Between two date )
        Route::post('dharamshala/get-application-financial-year-wise', 'getApplicationFinancialYearWise');           // 30 ( Get Application Financial Year Wise )
        Route::post('dharamshala/payment-collection', 'paymentCollection');                                          // 31 ( Get Application Financial Year Wise )
        Route::post('dharamshala/rule-wise-applications', 'ruleWiseApplications');                                   // 32 ( Get Application Rule Wise ).
        Route::post('dharamshala/get-application-by-organization-type', 'getApplicationByOrganizationType');         // 33 ( Get Application Organization type Wise )
    });


    /**
     * | Bandobastee Controller
     * | Controller - 11
     * | Created By - Bikash Kumar
     * | Date - 12 Aug 2023
     * | Status - Open
     */
    Route::controller(BandobasteeController::class)->group(function () {
        Route::post('bandobastee/bandobastee-master', 'bandobasteeMaster');                                  // 01   ( Get Stand Category )  
        Route::post('bandobastee/get-stand-category', 'getStandCategory');                                   // 02   ( Get Stand Category )  
        Route::post('bandobastee/get-stands', 'getStands');                                                  // 03   ( Get Stand and Category wise ULB )  
        Route::post('bandobastee/add-new', 'addNew');                                                        // 04   ( Save Application )  
        Route::post('bandobastee/list-penalty', 'listPenalty');                                              // 05   ( Get Panalty List ) 
        Route::post('bandobastee/list-settler', 'listSettler');                                              // 06   ( Get Stand Settler List )   
        Route::post('bandobastee/installment-payment', 'installmentPayment');                                // 07   ( Installment Payment )  
        Route::post('bandobastee/list-installment-payment', 'listInstallmentPayment');                       // 08   ( Installment Payment List )  
        Route::post('bandobastee/get-bandobastee-category', 'getBandobasteeCategory');                       // 09   ( Bandobastee List ) 
        Route::post('bandobastee/add-penalty-or-performance-security', 'addPenaltyOrPerformanceSecurity');   // 10   ( Add Penalty or Performance Security Money List )  
        Route::post('bandobastee/list-settler-transaction', 'listSettlerTransaction');                       // 11   ( Transaction List ) 
        Route::post('bandobastee/list-parking', 'listParking');                                              // 12   ( Parking List )
        Route::post('bandobastee/list-parking-settler', 'listParkingSettler');                               // 13   ( Parking Settler List )
        Route::post('bandobastee/list-bazar', 'listBazar');                                                  // 14   ( Bazar List )
        Route::post('bandobastee/list-bazar-settler', 'listBazarSettler');                                   // 15   ( Bazar Settler List )
    });
});
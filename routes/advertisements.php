@ -0,0 +1,274 @@
<?php

use App\Http\Controllers\Advertisements\AgencyController;
use App\Http\Controllers\Advertisements\HoardingController;
use App\Http\Controllers\Advertisements\PrivateLandController;
use App\Http\Controllers\Advertisements\SearchController;
use App\Http\Controllers\Advertisements\SelfAdvetController;
use App\Http\Controllers\Advertisements\VehicleAdvetController;
use App\Http\Controllers\Params\ParamController;
use Illuminate\Support\Facades\Route;

/**
 * | Created On - 12 Aug 2023
 * | Created By - Bikash Kumar
 * | Grievamne API 
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * | Self Advertisements
     * | Controller-01
     * | Status - Open By Bikash on 12 Aug 2023
     */
    Route::controller(SelfAdvetController::class)->group(function () {
        Route::post('self/add-new', 'addNew');                                                              // 01 ( Save Application )
        Route::post('self/get-application-details-for-renew', 'applicationDetailsForRenew');                // 02 ( Renew Application )
        Route::post('self/renewal-selfAdvt', 'renewalSelfAdvt');                                            // 03 ( Renew Application )
        Route::post('self/list-self-advt-category', 'listSelfAdvtCategory');                                // 04 ( Save Application )
        Route::post('self/list-inbox', 'listInbox');                                                        // 05 ( Application Inbox Lists )
        Route::post('self/list-outbox', 'listOutbox');                                                      // 06 ( Application Outbox Lists )
        Route::post('self/get-details-by-id', 'getDetailsById');                                            // 07 ( Get Application Details By Application ID )
        Route::post('self/list-applied-applications', 'listAppliedApplications');                           // 08 ( Get Applied Applications List By CityZen )
        Route::post('self/escalate-application', 'escalateApplication');                                    // 09 ( Escalate or De-escalate Application )
        Route::post('self/list-escalated', 'listEscalated');                                                // 10 ( Special Inbox Applications )
        Route::post('self/forward-next-level', 'forwordNextLevel');                                         // 11 ( Forward or Backward Application )
        Route::post('self/comment-application', 'commentApplication');                                      // 12 ( Independent Comment )
        Route::post('self/get-license-by-id', 'getLicenseById');                                            // 13 ( Get License By User ID )
        Route::post('self/get-license-by-holding-no', 'getLicenseByHoldingNo');                             // 14 ( Get License By Holding No ) 
        Route::post('self/view-advert-document', 'viewAdvertDocument');                                     // 15 ( Get Uploaded Document By Advertisement ID )
        Route::post('self/view-active-document', 'viewActiveDocument');                                     // 16 ( Get Uploaded Document By Advertisement ID )
        Route::post('self/get-details-by-license-no', 'getDetailsByLicenseNo');                             // 17 ( Get Uploaded Document By Advertisement ID )
        Route::post('self/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                          // 18 ( View Uploaded Document By Advertisement ID )
        Route::post('self/approved-or-reject', 'approvalOrRejection');                                      // 19 ( Approve or Reject )
        Route::post('self/list-approved', 'listApproved');                                                  // 20 ( Approved list for Citizen)
        Route::post('self/list-rejected', 'listRejected');                                                  // 21 ( Rejected list for Citizen)
        Route::post('self/get-jsk-applications', 'getJSKApplications');                                     // 22 ( Get Applied Applications List By JSK )
        Route::post('self/list-jsk-approved-application', 'listJskApprovedApplication');                    // 23 ( Approved list for JSK)
        Route::post('self/list-jsk-rejected-application', 'listJskRejectedApplication');                    // 24 ( Rejected list for JSK)    
        Route::post('self/generate-payment-order-id', 'generatePaymentOrderId');                            // 25 ( Generate Payment Order ID)
        Route::post('self/get-application-details-for-payment', 'applicationDetailsForPayment');            // 26 ( Application Details For Payments )
        Route::post('self/payment-by-cash', 'paymentByCash');                                               // 27 ( Payment via Cash )
        Route::post('self/entry-cheque-dd', 'entryChequeDd');                                               // 28 ( Entry Cheque or DD For Payments )
        Route::post('self/clear-or-bounce-cheque', 'clearOrBounceCheque');                                  // 29 ( Clear Cheque or DD )
        Route::post('self/verify-or-reject-doc', 'verifyOrRejectDoc');                                      // 30 ( Verify or Reject Document )
        Route::post('self/back-to-citizen', 'backToCitizen');                                               // 31 ( Application Back to Citizen )
        Route::post('self/list-btc-inbox', 'listBtcInbox');                                                 // 32 ( list Back to citizen )
        Route::post('self/reupload-document', 'reuploadDocument');                                          // 33 ( Reupload Rejected Document )
        Route::post('self/search-by-name-or-mobile', 'searchByNameorMobile');                               // 34 ( Search application by name and mobile no )
        Route::post('self/get-application-between-date', 'getApplicationBetweenDate');                      // 35 ( Get Application Between two date )
        Route::post('self/get-application-financial-year-wise', 'getApplicationFinancialYearWise');         // 36 ( Get Application Financial Year Wise )
        Route::post('self/get-application-display-wise', 'getApplicationDisplayWise');                      // 37 ( Get Application Financial Year Wise )
        Route::post('self/payment-collection', 'paymentCollection');                                        // 38 ( Get Application Financial Year Wise )
    });

    /**
     * | Param Strings 
     * | Controller-02
     * | Status - Open By Bikash on 12 Aug 2023
     */
    Route::controller(ParamController::class)->group(function () {
        Route::post('crud/param-strings', 'paramStrings');                                                  // 01 ( Get Param String List)
        Route::post('get-approval-letter', 'getApprovalLetter');                                            // 02 ( Get All Approval Letter )
        Route::post('crud/v1/list-document', 'listDocument');                                               // 03 ( Applied Document List )
        // Route::post('crud/district-mstrs', 'districtMstrs');                                             // 04
        Route::post('payment-success-failure', 'paymentSuccessFailure');                                    // 05 ( Update Payment Success or Failure )
        Route::post('dashboard', 'advertDashboard');                                                        // 06 ( Advertisement Dashboard )
        Route::post('search-by-name-or-mobile', 'searchByNameOrMobile');                                    // 07 ( Search Application By Mobile or Name )
        Route::post('market/dashboard', 'marketDashboard');                                                 // 08 ( Market Dashboard )
        Route::post('get-payment-details', 'getPaymentDetails');                                            // 09 ( Application Details For Payments )
        Route::post('send-whatsapp-notification', 'sendWhatsAppNotification');                              // 10 ( Application Details For Payments )
        Route::post('application-reports', 'applicationReports');                                           // 11 ( Application Reports )
        Route::post('get-financial-year-master-data', 'getFinancialMasterData');                            // 12 ( Get Financial Year For Search )
        Route::post('advertisement-dashboard', 'advertisementDashboard');                                   // 13 ( Advertisement Dashboard )
    });

    /**
     * | Movable Vehicles 
     * | Controller-03
     * | Status - Open By Bikash on 12 Aug 2023
     */
    Route::controller(VehicleAdvetController::class)->group(function () {
        Route::post('vehicle/add-new', 'addNew');                                                        // 01 ( Save Application )
        Route::post('vehicle/get-application-details-for-renew', 'applicationDetailsForRenew');          // 02 ( Renew Application )
        Route::post('vehicle/renewal-application', 'renewalApplication');                                // 03 ( Renew Application )
        Route::post('vehicle/list-inbox', 'listInbox');                                                  // 04 ( Application Inbox Lists )
        Route::post('vehicle/list-outbox', 'listOutbox');                                                // 05 ( Application Outbox Lists )
        Route::post('vehicle/get-details-by-id', 'getDetailsById');                                      // 06 ( Get Application Details By Application ID )
        Route::post('vehicle/list-applied-applications', 'listAppliedApplications');                     // 07 ( Get Applied Applications List )
        Route::post('vehicle/escalate-application', 'escalateApplication');                              // 08 ( Escalate or De-escalate Application )
        Route::post('vehicle/list-escalated', 'listEscalated');                                          // 09 ( Special Inbox Applications )
        Route::post('vehicle/forward-next-level', 'forwardNextLevel');                                   // 10 ( Forward or Backward Application )
        Route::post('vehicle/comment-application', 'commentApplication');                                // 11 ( Independent Comment )
        Route::post('vehicle/view-vehicle-documents', 'viewVehicleDocuments');                           // 12 ( Get Uploaded Document By Application ID )
        Route::post('vehicle/view-active-document', 'viewActiveDocument');                               // 13 ( Get Uploaded Document By Advertisement ID )
        Route::post('vehicle/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                    // 14 ( Get Uploaded Document By Application ID )
        Route::post('vehicle/approved-or-reject', 'approvedOrReject');                                   // 15 ( Approve or Reject )
        Route::post('vehicle/list-approved', 'listApproved');                                            // 16 ( Approved list for Citizen)
        Route::post('vehicle/list-rejected', 'listRejected');                                            // 17 ( Rejected list for Citizen)
        Route::post('vehicle/get-jsk-applications', 'getJSKApplications');                               // 18 ( Get Applied Applications List By JSK )
        Route::post('vehicle/list-jsk-approved-application', 'listjskApprovedApplication');              // 19 ( Approved list for JSK)
        Route::post('vehicle/list-jsk-rejected-application', 'listJskRejectedApplication');              // 20 ( Rejected list for JSK)  
        Route::post('vehicle/generate-payment-order-id', 'generatePaymentOrderId');                      // 21 ( Generate Payment Order ID)
        Route::post('vehicle/get-application-details-for-payment', 'getApplicationDetailsForPayment');   // 22 ( Application Details For Payments )
        Route::post('vehicle/payment-by-cash', 'paymentByCash');                                         // 23 ( Payment Via Cash )
        Route::post('vehicle/entry-cheque-dd', 'entryChequeDd');                                         // 24 ( Entry Cheque or DD For Payments )
        Route::post('vehicle/clear-or-bounce-cheque', 'clearOrBounceCheque');                            // 25 ( Clear or Bouns Cheque For Payments )
        Route::post('vehicle/entry-zone', 'entryZone');                                                  // 26 ( Entry Zone by Permitted Canidate )
        Route::post('vehicle/verify-or-reject-doc', 'verifyOrRejectDoc');                                // 27 ( Verify or Reject Document)
        Route::post('vehicle/back-to-citizen', 'backToCitizen');                                         // 28 ( Application Back to citizen )
        Route::post('vehicle/list-btc-inbox', 'listBtcInbox');                                           // 29 ( list Application Back to citizen )
        Route::post('vehicle/reupload-document', 'reuploadDocument');                                    // 30 ( Reupload Rejected Document )
        Route::post('vehicle/get-application-between-date', 'getApplicationBetweenDate');                //31 ( Get Application Between two date )
        Route::post('vehicle/payment-collection', 'paymentCollection');                                  //32 ( Get Application Financial Year Wise )
    });

    /**
     * | Private Lands
     * | Controller-04 
     * | Status - Open By Bikash on 12 Aug 2023
     */
    Route::controller(PrivateLandController::class)->group(function () {
        Route::post('pvt-land/add-new', 'addNew');                                                      // 01   ( Save Application )  
        Route::post('pvt-land/get-application-details-for-renew', 'applicationDetailsForRenew');        // 02 ( Renew Application )
        Route::post('pvt-land/renewal-application', 'renewalApplication');                              // 03 ( Renew Application ) 
        Route::post('pvt-land/list-inbox', 'listInbox');                                                // 04 ( Application Inbox Lists )
        Route::post('pvt-land/list-outbox', 'listOutbox');                                              // 05 ( Application Outbox Lists )
        Route::post('pvt-land/get-details-by-id', 'getDetailsById');                                    // 06 ( Get Application Details By Application ID )
        Route::post('pvt-land/list-applied-applications', 'listAppliedApplications');                   // 07 ( Get Applied Applications List )
        Route::post('pvt-land/escalate-application', 'escalateApplication');                            // 08 ( Escalate or De-escalate Application )
        Route::post('pvt-land/list-escalated', 'listEscalated');                                        // 09 ( Special Inbox Applications )
        Route::post('pvt-land/forward-next-level', 'forwardNextLevel');                                 // 10 ( Forward or Backward Application )
        Route::post('pvt-land/comment-application', 'commentApplication');                              // 11 ( Independent Comment )
        Route::post('pvt-land/view-pvt-land-documents', 'viewPvtLandDocuments');                        // 12 ( Get Uploaded Document By Application ID )
        Route::post('pvt-land/view-active-document', 'viewActiveDocument');                             // 13 ( Get Uploaded Document By Advertisement ID )
        Route::post('pvt-land/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                  // 14 ( Get Uploaded Document By Application ID )
        Route::post('pvt-land/approved-or-reject', 'approvedOrReject');                                 // 15 ( Approve or Reject )
        Route::post('pvt-land/list-approved', 'listApproved');                                          // 16 ( Approved list for Citizen)
        Route::post('pvt-land/list-rejected', 'listRejected');                                          // 17 ( Rejected list for Citizen)
        Route::post('pvt-land/get-jsk-applications', 'getJSKApplications');                             // 18 ( Get Applied Applications List By JSK )
        Route::post('pvt-land/list-jsk-approved-application', 'listjskApprovedApplication');            // 19 ( Approved list for JSK)
        Route::post('pvt-land/list-jsk-rejected-application', 'listJskRejectedApplication');            // 20 ( Rejected list for JSK)  
        Route::post('pvt-land/generate-payment-order-id', 'generatePaymentOrderId');                    // 21 ( Generate Payment Order ID)
        Route::post('pvt-land/get-application-details-for-payment', 'getApplicationDetailsForPayment'); // 22 ( Application Details For Payments )
        Route::post('pvt-land/payment-by-cash', 'paymentByCash');                                       // 23 ( Payment Via Cash )
        Route::post('pvt-land/entry-cheque-dd', 'entryChequeDd');                                       // 24 ( Entry Check or DD for Payment )
        Route::post('pvt-land/clear-or-bounce-cheque', 'clearOrBounceCheque');                          // 25 ( Clear or Bouns Check )
        Route::post('pvt-land/entry-zone', 'entryZone');                                                // 26 ( Zone Entry by permitted member )
        Route::post('pvt-land/verify-or-reject-doc', 'verifyOrRejectDoc');                              // 27 ( Verify or Reject Document )
        Route::post('pvt-land/back-to-citizen', 'backToCitizen');                                       // 28 ( Application Back to Citizen )
        Route::post('pvt-land/list-btc-inbox', 'listBtcInbox');                                         // 29 ( list BTC Inbox )
        Route::post('pvt-land/reupload-document', 'reuploadDocument');                                  // 30 ( Reupload Rejected Documents )
        Route::post('pvt-land/get-application-between-date', 'getApplicationBetweenDate');              // 31 ( Get Application Between two date )
        Route::post('pvt-land/get-application-display-wise', 'getApplicationDisplayWise');              // 32 ( Get Application Financial Year Wise )
        Route::post('pvt-land/payment-collection', 'paymentCollection');                                // 33 ( Get Application Financial Year Wise )
    });

    /**
     * | Agency 
     * | Controller-05 
     * | Status - Closed By Bikash on 24 Apr 2023
     */
    Route::controller(AgencyController::class)->group(function () {
        Route::post('agency/add-new', 'addNew');                                                        // 01   ( Save Application )
        Route::post('agency/get-agency-details', 'getAgencyDetails');                                   // 02  ( Agency Details )
        Route::post('agency/list-inbox', 'listInbox');                                                  // 03 ( Application Inbox Lists )
        Route::post('agency/list-outbox', 'listOutbox');                                                // 04 ( Application Outbox Lists )
        Route::post('agency/get-details-by-id', 'getDetailsById');                                      // 05 ( Get Application Details By Application ID )
        Route::post('agency/list-applied-applications', 'listAppliedApplications');                     // 06 ( Get Applied Applications List )
        Route::post('agency/escalate-application', 'escalateApplication');                              // 07 ( Escalate or De-escalate Application )
        Route::post('agency/list-escalated', 'listEscalated');                                          // 08 ( Special Inbox Applications )
        Route::post('agency/forward-next-level', 'forwardNextLevel');                                   // 09 ( Forward or Backward Application )
        Route::post('agency/comment-application', 'commentApplication');                                // 10 ( Independent Comment )
        Route::post('agency/view-agency-documents', 'viewAgencyDocuments');                             // 11 ( Get Uploaded Document By Application ID )
        Route::post('agency/view-active-document', 'viewActiveDocument');                               // 12 ( Get Uploaded Document By Advertisement ID )
        Route::post('agency/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                    // 13 ( Get Uploaded Document By Application ID )
        Route::post('agency/approved-or-reject', 'approvedOrReject');                                   // 14 ( Approve or Reject )
        Route::post('agency/list-approved', 'listApproved');                                            // 15 ( Approved list for Citizen)
        Route::post('agency/list-rejected', 'listRejected');                                            // 16 ( Rejected list for Citizen)
        Route::post('agency/get-jsk-applications', 'getJSKApplications');                               // 17 ( Get Applied Applications List By JSK )
        Route::post('agency/list-jsk-approved-application', 'listjskApprovedApplication');              // 18 ( Approved list for JSK)
        Route::post('agency/list-jsk-rejected-application', 'listJskRejectedApplication');              // 19 ( Rejected list for JSK)  
        Route::post('agency/generate-payment-order-id', 'generatePaymentOrderId');                      // 20 ( Generate Payment Order ID)
        Route::post('agency/get-application-details-for-payment', 'getApplicationDetailsForPayment');   // 21 ( Application Details For Payments )
        Route::post('agency/renewal-agency', 'renewalAgency');                                          // 22 ( Application Details For Payments )
        Route::post('agency/payment-by-cash', 'agencyPaymentByCash');                                   // 23 ( Application Details For Payments )
        Route::post('agency/entry-cheque-dd', 'entryChequeDd');                                         // 24 ( Application Details For Payments )
        Route::post('agency/clear-or-bounce-cheque', 'clearOrBounceCheque');                            // 25 ( Application Details For Payments )
        Route::post('agency/verify-or-reject-doc', 'verifyOrRejectDoc');                                // 26 ( Application Details For Payments )
        Route::post('agency/back-to-citizen', 'backToCitizen');                                         // 27 ( Application Details For Payments )
        Route::post('agency/list-btc-inbox', 'listBtcInbox');                                           // 28 ( Application Details For Payments )
        Route::post('agency/reupload-document', 'reuploadDocument');                                    // 29 ( Application Details For Payments )
        Route::post('agency/search-by-name-or-mobile', 'searchByNameorMobile');                         //30 ( Search application by name and mobile no )
        Route::post('agency/is-agency', 'isAgency');                                                    // 31 (Get Agency Approve or not By Login Token)
        Route::post('agency/get-agency-dashboard', 'getAgencyDashboard');                               //32 (Get Agency Dashboard)
        Route::post('agency/get-application-between-date', 'getApplicationBetweenDate');                //33 ( Get Application Between two date )
        Route::post('agency/payment-collection', 'paymentCollection');                                  //34 ( Get Application Financial Year Wise )
        Route::post('agency/is-email-available', 'isEmailAvailable');                                   //35 ( Check email is free for agency or not )
    });


    /**
     * | Hoarding 
     * | Controller-06 
     * | Status - Open By Bikash on 12 Aug 2023
     */
    Route::controller(HoardingController::class)->group(function () {
        Route::post('hording/get-hording-category', 'getHordingCategory');                              // 01 ( Get Typology List )
        Route::post('hording/list-typology', 'listTypology');                                           // 02 ( Get Typology List )
        Route::post('hording/add-new', 'addNew');                                                       // 03 ( Save Application For Licence )
        Route::post('hording/list-inbox', 'listInbox');                                                 // 04 ( Application Inbox Lists )
        Route::post('hording/list-outbox', 'listOutbox');                                               // 05 ( Application Outbox Lists )
        Route::post('hording/get-details-by-id', 'getDetailsById');                                     // 06 ( Get Application Details By Application ID )
        Route::post('hording/list-applied-applications', 'listAppliedApplications');                    // 07 ( Get Applied Applications List )
        Route::post('hording/escalate-application', 'escalateApplication');                             // 08 ( Escalate or De-escalate Application )
        Route::post('hording/list-escalated', 'listEscalated');                                         // 09 ( Special Inbox Applications )
        Route::post('hording/forward-next-level', 'forwardNextLevel');                                  // 10 ( Forward or Backward Application )
        Route::post('hording/comment-application', 'commentApplication');                               // 11 ( Independent Comment )
        Route::post('hording/view-hoarding-documents', 'viewHoardingDocuments');                        // 12 ( Get Uploaded Document By Application ID )
        Route::post('hording/view-active-document', 'viewActiveDocument');                              // 13 ( Get Uploaded Document By Advertisement ID )
        Route::post('hording/view-documents-on-workflow', 'viewDocumentsOnWorkflow');                   // 14 ( Get Uploaded Document By Application ID )
        Route::post('hording/approval-or-rejection', 'approvalOrRejection');                            // 15 ( Approve or Reject )
        Route::post('hording/list-approved', 'listApproved');                                           // 16 ( License Approved list for Citizen)
        Route::post('hording/list-rejected', 'listRejected');                                           // 17 ( License Rejected list for Citizen)
        Route::post('hording/list-unpaid', 'listUnpaid');                                               // 18 ( License Rejected list for Citizen)
        Route::post('hording/get-jsk-applications', 'getJskApplications');                              // 19 ( Get Applied Applications List By JSK )
        Route::post('hording/list-jsk-approved-application', 'listJskApprovedApplication');             // 20 ( Approved list for JSK)
        Route::post('hording/list-jsk-rejected-application', 'listJskRejectedApplication');             // 21 ( Rejected list for JSK)  
        Route::post('hording/generate-payment-order-id', 'generatePaymentOrderId');                     // 22 ( Generate Payment Order ID)
        Route::post('hording/get-application-details-for-payment', 'getApplicationDetailsForPayment');  // 23 ( Application Details For Payments )
        Route::post('hording/get-hording-details-for-renew', 'getHordingDetailsForRenew');              // 24 ( Application Details For Payments )
        Route::post('hording/renewal-hording', 'renewalHording');                                       // 25 ( Application Details For Payments )
        Route::post('hording/payment-by-cash', 'paymentByCash');                                        // 26 ( Application Details For Payments )
        Route::post('hording/entry-cheque-dd', 'entryChequeDd');                                        // 27 ( Application Details For Payments )
        Route::post('hording/clear-or-bounce-cheque', 'clearOrBounceCheque');                           // 28 ( Application Details For Payments )
        Route::post('hording/verify-or-reject-doc', 'verifyOrRejectDoc');                               // 29 ( Application Details For Payments )
        Route::post('hording/back-to-citizen', 'backToCitizen');                                        // 30 ( Application Details For Payments )
        Route::post('hording/list-btc-inbox', 'listBtcInbox');                                          // 31 ( Application Details For Payments )
        Route::post('hording/reupload-document', 'reuploadDocument');                                   // 32 ( Application Details For Payments )
        Route::post('hording/get-renew-active-applications', 'getRenewActiveApplications');             // 33 (Get Agency Dashboard)
        Route::post('hording/list-expired-hording', 'listExpiredHording');                              // 34 (Get Expired Hording)
        Route::post('hording/archived-hording', 'archivedHording');                                     // 35 (Archieves Hording)
        Route::post('hording/list-hording-archived', 'listHordingArchived');                            // 36 (list Expired Hording)
        Route::post('hording/blacklist-hording', 'blacklistHording');                                   // 37 (Blacklist Hording)
        Route::post('hording/list-hording-blacklist', 'listHordingBlacklist');                          // 38 (list Blacklist Hording)
        Route::post('hording/agency-dashboard-graph', 'agencyDashboardGraph');                          // 39 (list Blacklist Hording)
        Route::post('hording/get-application-between-date', 'getApplicationBetweenDate');               // 40 ( Get Application Between two date )
        Route::post('hording/get-application-financial-year-wise', 'getApplicationFinancialYearWise');  // 41 ( Get Application Financial Year Wise )
        Route::post('hording/payment-collection', 'paymentCollection');                                 // 42 ( Get Application Financial Year Wise )
        Route::post('hoarding/get-agency-dashboard', 'getAgencyDashboard');                             // 43 (Get Agency Dashboard)

    });

    /**
     * | Search Controller
     * | Controller-12
     * | Created By - Bikash Kumar
     * | Date - 12 Aug 2023
     * | Status - Open
     */
    Route::controller(SearchController::class)->group(function () {
        Route::post('search/list-all-advertisement-records', 'listAllAdvertisementRecords');                              // 01   ( All Advertisement records List  of citizen )
        Route::post('search/list-all-market-records', 'listAllMarketRecords');                                            // 02   ( All Market records List  of citizen )
    });
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiMasterController;
use App\Http\Controllers\BugReporting\BugController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SelfAdvertisementController;
use App\Http\Controllers\Service\IdGeneratorController;
use App\Http\Controllers\ThirdPartyController;
use App\Http\Controllers\UlbController;
use App\Http\Controllers\UlbWorkflowController;
use App\Http\Controllers\Workflows\WorkflowController;
use App\Http\Controllers\Workflows\WorkflowTrackController;
use App\Http\Controllers\Ward\WardController;
use App\Http\Controllers\WcController;
use App\Http\Controllers\WorkflowMaster\RoleController;
use App\Http\Controllers\Workflows\UlbWorkflowRolesController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleUserMapController;
use App\Http\Controllers\CaretakerController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------

*/
Route::group(['middleware' => ['auth:sanctum','json.response','expireBearerToken','request_logger']], function () {
 
    Route::post('test',[BugController::class,'test']);
        // Route::post("test","createbugsform");
        // Route::post("test","createbugsform");

        Route::controller(BugController::class)->group(function()
        {
            Route::post('testw','test');
            Route::post('add-bugs','createbugsform');
            Route::post('module-list','moduleList');
            Route::post('case-list', 'category');
            Route::post('register-apply-data', 'allformlist');
            

        });
});



<?php

namespace App\Http\Controllers\BugReporting;

use App\Http\Controllers\Controller;
use App\Models\BugReporting\Bug;
use App\Models\ModuleMaster;
use Dotenv\Validator;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Svg\Tag\Rect;
use App\MicroServices\DocUpload;

class BugController extends Controller
{
     /**
     * Controller for Add, Update, View , Delete of Bugs
     * -------------------------------------------------------------------------------------------------
     * Created On-27-06-2023
     * Created By-Tannu Verma
     * Status : open 
     * -------------------------------------------------------------------------------------------------
     */



    //  create bugsform
    public function createbugsform(Request $request)
   {
    $validated = FacadesValidator::make($request->all(), [
        'menuId' => 'required',
        'menuDescription' => 'required',
        'bugTitle' => 'required',
        'bugSummary' => 'required',
        'environmentDescription' => 'required',
        'categoryId' => 'required',
        'severity' => 'required',
        'priority'=>'required',
        //'status' => 'required',
        //'reportingUserId' => 'required',
        //'assignedTo' => 'required',
        'screenBitmap' => 'required',
        'moduleId' => 'required',
        //'caseId' => 'required'
    ]);

    if ($validated->fails()) {
        return validationError($validated);
    }
    
    try {
        $docUpload = new DocUpload();
        $path = "Uploads/Bug/";
        $refImageName = "Bug";
        $document = $request->screenBitmap;

        $imageName = $docUpload->upload($refImageName, $document, $path);
        $newrequest = collect($request->all());
        $newrequest->pull("screenBitmap");
        $newrequest = new Request($newrequest->toArray());
        $newrequest->merge(["screenBitmap"=>$path.$imageName]);

        $create = new Bug();
        // dd($request->all(),$newrequest->all());
       $data= $create->addBugsForm($newrequest);
        
        return responseMsg(true, "successfully saved", "");
    } catch(Exception $e) {
        return responseMsg(false, $e->getMessage(), "");
    }
   }


    // Module List
    public function moduleList(Request $request)
    {
        try{
            $list = new Bug();
            $modulelist = $list->moduleList($request);

            return responseMsg(true, "Module List", $modulelist);
        }catch(Exception $e){
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    
    public function category(Request $request)
    {
        try{
            return authUser();
            $list = new Bug();
            $caselist = $list->category($request);
            
            return responseMsg(true, "Case List", $caselist);
        } catch(Exception $e){
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    public function allformlist(Request $request)
    {
        try{
            $data = new Bug();
            $getdata = $data->allformlist($request);

            return responseMsg(true, "Module, Category, Severity and Priority List", remove_null($getdata));
           }catch (Exception $e){
            return responseMsg(false, $e->getMessage(), "");
           }

    }



    



}

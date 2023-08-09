<?php

namespace App\Models\BugReporting;

use App\Models\ModuleMaster;
use App\Models\ParamCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Bug extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
    

    //create bugsform
    public function addBugsForm($request)
    {
        $data = new Bug();
        $data->menu_id = $request->menuId;
        $data->menu_description = $request->menuDescription;
        $data->bug_title = $request->bugTitle;
        $data->bug_summary = $request->bugSummary;
        $data->environment_description = $request->environmentDescription;
        $data->category_id = $request->categoryId;
        $data->severity = $request->severity;
        $data->priority = $request->priority;
        //$data->status = $request->status;
        $data->reporting_user_id = $request->reportingUserId;
        $data->reporting_time = Carbon::now();
        //$data->assigned_to = $request->assignedTo;
        $data->assignment_time = Carbon::now();
        $data->screen_bitmap = $request->screenBitmap;
        $data->module_id = $request->moduleId;
        //$data->case_id = $request->caseId;
        $data->save();
    }

    
    //for modulelist
    public function moduleList()
    {
        $moduleList = ModuleMaster::select('id', 'module_name', 'status')
        ->where('status', 1)
        ->orderBy('id') 
        ->get();       
        return $moduleList;        

    }


    //for case list
    public function category()
    {
        $caselist = Category::all();
        return $caselist;
    }


    //for module and case list
    public function allformlist()
    {
        $moduleList = ModuleMaster::select('id', 'module_name', 'status')
        ->where('status', 1)
        ->orderBy('id')
        ->get();

        $category = Category::where('status', 1)
        ->orderBy('id')
        ->get();

        $severity = Severity::where('status', 1)
        ->orderBy('id')
        ->get();

        $priority = Priority::where('status', 1)
        ->orderBy('id')
        ->get();

        $getdata = [
            "moduleList" => $moduleList,
            "category" => $category, 
            "severity" => $severity,
            "priority" => $priority
        ];

        return $getdata;
    }



    // $data = new MarriageActiveRegistration();

    // $data->ulb_id = $request->ulbId;
    // $data->bride_name = $request->brideName;
    // $data->bride_dob = $request->brideDob;
    // $data->bride_age = $request->brideAge;
    // $data->bride_nationality = $request->brideNationality;
    // $data->bride_religion = $request->brideReligion;
    // $data->bride_mobile = $request->brideMobile;
    // $data->bride_aadhar_no = $request->brideAadharNo;
    // $data->bride_email = $request->brideEmail;
    // $data->bride_passport_no = $request->bridePassportNo;
    // $data->bride_residential_address = $request->brideResidentialAddress;
    // $data->bride_martial_status = $request->brideMartialStatus;
    // $data->bride_father_name = $request->brideFatherName;
    // $data->bride_father_aadhar_no = $request->brideFatherAadharNo;
    // $data->bride_mother_name = $request->brideMotherName;
    // $data->bride_mother_aadhar_no = $request->brideMotherAadharNo;
    // $data->bride_guardian_name = $request->brideGuardianName;
    // $data->bride_guardian_aadhar_no = $request->brideGuardianAadharNo;
    // $data->groom_name = $request->groomName;
    // $data->groom_dob = $request->groomDob;
    // $data->groom_age = $request->groomAge;
    // $data->groom_nationality = $request->groomNationality;
    // $data->groom_religion = $request->groomReligion;
    // $data->groom_mobile = $request->groomMobile;
    // $data->groom_passport_no = $request->groomPassportNo;
    // $data->groom_residential_address = $request->groomResidentialAddress;
    // $data->groom_martial_status = $request->groomMartialStatus;
    // $data->groom_father_name = $request->groomFatherName;
    // $data->groom_father_aadhar_no = $request->groomFatherAadharNo;
    // $data->groom_mother_name = $request->groomMotherName;
    // $data->grrom_mother_aadhar_no = $request->groomMotherAadharNo;
    // $data->groom_guardian_name = $request->groomGuardianName;
    // $data->groom_guardian_aadhar_no = $request->groomGuardianAadharNo;
    // $data->marriage_date = $request->marriageDate;
    // $data->marriage_place = $request->marriagePlace;
    // $data->marriage_type_ = $request->marriageType;
    // $data->witness1_name = $request->witness1Name;
    // $data->witness1_mobile_no = $request->witness1MobileNo;
    // $data->witness1_residential_address = $request->witness1ResidentialAddress;
    // $data->witness2_name= $request->witness2Name;
    // $data->witness2_residential_address = $request->witness2ResidentialAddress;
    // $data->witness2_mobile_no = $request->witness2MobileNo;
    // $data->witness3_name = $request->witness3Name;
    // $data->witness3_mobile_no = $request->witnessMobileNo;
    // $data->witness3_residential_address = $request->witness3ResidentialAddress;
    // $data->appointment_date = $request->appointmentDate;
    // $data->marriage_registration_date = $request->marriageRegistrationDate;
    // $data->register_id = $request->registerId;
    // $data->doc_upload_status = $request->docUploadStatus;
    // $data->payment_status = $request->paymentStatus;
    // $data->payment_amount = $request->paymentAmount;
    // $data->penalty_amount = $request->penaltyAmount;
    // $data->user_id = $request->userId;
    // $data->citizen_id = $request->citizenId;
    // $data->parked = $request->parked;
    // $data->status = $request->status;

    // $data->save();










}

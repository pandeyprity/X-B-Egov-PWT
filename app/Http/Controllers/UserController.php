<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\AuthorizeRequestUser;
use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Http\Requests\Auth\OtpChangePass as AuthOtpChangePass;
use App\Models\User;
use App\Repository\Auth\EloquentAuthRepository;
use App\Traits\Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

/**
 * Controller for user data login, logout, changing password
 * Child Repository => App\Repository\Auth
 * Creation Date:-24-06-2022
 * Created By:- Anshu Kumar
 * 
 *  ** Code Test  **
 * Code Tested By-Anil Mishra Sir
 * Code Testing Date-24-06-2022
 */

class UserController extends Controller
{
    // Initializing for Repository
    protected $eloquentAuth;

    public function __construct(EloquentAuthRepository $eloquentAuth)
    {
        $this->EloquentAuth = $eloquentAuth;
    }

    // Store User In Database
    public function store(AuthUserRequest $request)
    {
        return $this->EloquentAuth->store($request);
    }

    // Store the user in database from Authority
    public function authorizeStore(AuthorizeRequestUser $request)
    {
        $request['ulb'] = authUser($request)->ulb_id;
        return $this->EloquentAuth->store($request);
    }

    // Update User Details
    public function update(Request $request)
    {
        return $this->EloquentAuth->update($request);
    }

    // User Authentication
    public function loginAuth(LoginUserRequest $request)
    {
        return $this->EloquentAuth->loginAuth($request);
    }

    // User Logout
    public function logOut()
    {
        return $this->EloquentAuth->logOut();
    }

    // Changing Password
    public function changePass(ChangePassRequest $request)
    {
        return $this->EloquentAuth->changePass($request);
    }

    // Redis Test Function
    public function testing()
    {
        return $this->EloquentAuth->testing();
    }

    // Get All Users
    public function getAllUsers()
    {
        return $this->EloquentAuth->getAllUsers();
    }

    // Get User by Ids
    public function getUser($id)
    {
        return $this->EloquentAuth->getUser($id);
    }

    /**
     * ----------------------------------------------------------------------------------
     * Current Logged In Users
     * ----------------------------------------------------------------------------------
     */
    // My Profile Details
    public function myProfileDetails()
    {
        return $this->EloquentAuth->myProfileDetails();
    }

    // Edit My Profile Details
    public function editMyProfile(Request $request)
    {
        return $this->EloquentAuth->editMyProfile($request);
    }

    // Edit My Profile Details
    public function deleteUser(Request $request)
    {
        return $this->EloquentAuth->deleteUser($request);
    }

    public function employeeList(Request $request)
    {
        return $this->EloquentAuth->employeeList($request);
    }

    //user notification
    public function userNotification(Request $request)
    {
        return $this->EloquentAuth->userNotification($request);
    }

    /**
     * | add Notificatio
     */
    public function addNotification(Request $request)
    {
        return $this->EloquentAuth->addNotification($request);
    }

    /**
     * | deactivate Notification
     */
    public function deactivateNotification(Request $request)
    {
        return $this->EloquentAuth->deactivateNotification($request);
    }

    /**
     * | Change Password by OTP 
     * | Api Used after the OTP Validation
     */
    public function changePasswordByOtp(AuthOtpChangePass $request)
    {
        try {
            $id = authUser($request)->id;
            $user = User::find($id);
            $user->password = Hash::make($request->password);
            $user->save();

            Redis::del('user:' . authUser($request)->id);   //DELETING REDIS KEY
            return response()->json(['Status' => 'True', 'Message' => 'Successfully Changed the Password'], 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    public function hashPassword()
    {
        $datas =  User::select('id', 'password', "old_password")->where('password', '121')->orderby('id')->get();

        foreach ($datas as $data) {
            $user = User::find($data->id);
            if (!$user || $user->password != '121') {
                continue;
            }
            DB::beginTransaction();
            $user->password = Hash::make($data->old_password);
            $user->update();
            DB::commit();
        }
    }
}

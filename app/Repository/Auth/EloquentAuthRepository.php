<?php

namespace App\Repository\Auth;

use App\BLL\AddNotification;
use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Models\MirrorUserNotification;
use App\Models\User;
use App\Models\UserNotification;
use App\Repository\Auth\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Exception;
use App\Traits\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/*
 * Parent Controller:-App\Http\Controller\UserController 
 * ----------------------------------------------------------------------------------------------
 * Author Name-Anshu Kumar
 * ----------------------------------------------------------------------------------------------
 * Start Date  - 24-06-2022
 * Finish Date - 24-06-2022
 * ----------------------------------------------------------------------------------------------
 * Coding Test
 * ----------------------------------------------------------------------------------------------
 * Code Tested By - Anil Sir
 * Code Testing Date - 24-06-2022
 * Feedback- 
 * 
 */

class EloquentAuthRepository implements AuthRepository
{
    use Auth;
    /**
     * -----------------------------------------------
     * Parent Controller- function Store()
     * -----------------------------------------------
     * @param App\Http\Requests\AuthUserRequest
     * @param App\Http\Requests\AuthUserRequest $request
     */

    public function store($request)
    {
        try {
            // Validation---@source-App\Http\Requests\AuthUserRequest
            $user = new User;
            $this->saving($user, $request);                     // Storing data using Auth trait
            $user->password = Hash::make($request->password);
            $user->save();
            return responseMsg(true, "User Registered Successfully !! Please Continue to Login", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Editing Users
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @param user_id $id
     * --------------------------------------------------------------------------------------------
     * validate
     * Checking if the request email is already existing of not
     * update using AuthTrait
     */

    public function update(Request $request)
    {
        $request->validate([
            "id" => 'required',
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255']
        ]);
        try {
            $id = $request->id;
            $user = User::find($id);
            $stmt = $user->email == $request->email;
            if ($stmt) {
                $this->saving($user, $request);
                $this->savingExtras($user, $request);
                $user->save();
                Redis::del('user:' . $id);                                  //Deleting Key from Redis Database
                $message = ["status" => true, "message" => "Successfully Updated", "data" => ''];
                return response()->json($message, 200);
            }
            if (!$stmt) {
                $check = User::where('email', '=', $request->email)->first();
                if ($check) {
                    $message = ["status" => false, "message" => "Email Is Already Existing", "data" => ''];
                    return response()->json($message, 200);
                }
                if (!$check) {
                    $this->saving($user, $request);
                    $this->savingExtras($user, $request);
                    $user->save();
                    Redis::del('user:' . $id);                               //Deleting Key from Redis Database
                    $message = ["status" => true, "message" => "Successfully Updated", "data" => ''];
                    return response()->json($message, 200);
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * ------------------------------------------------
     * @Parent Controller- function loginAuth()
     * ------------------------------------------------
     * @param App\Http\Requests\LoginUserRequest
     * @param App\Http\Requests\LoginUserRequest $request
     * -------------------------------------------------
     * Function LoginAuth 
     * -------------------------------------------------
     * validate email password
     * Check if the user Existing or Not    (OK)
     * Check if the User is Suspended or Not    (OK)
     * Check UserExist && Data Present On Redis Database -> Check Password (OK)
     * If Data not Present on Redis Database -> Authentication Check by SQL Database    (OK)
     */

    public function loginAuth(LoginUserRequest $request)
    {
        try {
            // Validation
            $validator = $request->validated();
            $email = $request->email;
            // checking user is existing or not
            $emailInfo = User::where('email', $request->email)
                ->first();
            if (!$emailInfo) {
                $msg = "Oops! Given email does not exist";
                return responseMsg(false, $msg, "");
            }

            // check if suspended user
            if ($emailInfo->suspended == true) {
                $msg = "Cant logged in!! You Have Been Suspended !";
                return responseMsg(false, $msg, "");
            }

            // Redis Authentication if data already existing in redis database
            $redis = Redis::connection();
            /*   if email exists then the condition applies  */
            if ($emailInfo && $user = Redis::get('user:' . $emailInfo->id)) {
                $info = json_decode($user);
                // AUTHENTICATING PASSWORD IN HASH
                if (Hash::check($request->password, $info->password)) {
                    $token = $emailInfo->createToken('my-app-token')->plainTextToken;
                    $emailInfo->remember_token = $token;
                    $emailInfo->save();

                    $this->redisStore($redis, $emailInfo, $request, $token);   // Trait for update Redis

                    Redis::expire('user:' . $emailInfo->id, 18000);         // EXPIRE KEY AFTER 5 HOURS
                    $key = 'last_activity_' . $emailInfo->id;               // Set last activity key 
                    Redis::set($key, Carbon::now());

                    $message = $this->tResponseSuccess($token, $email, $request);     // Response Message Using Trait
                    return response()->json($message, 200);
                }
                // AUTHENTICATING PASSWORD IN HASH
                else {
                    $msg = "Incorrect Password";
                    $message = $this->tResponseFail($msg);               // Response Message Using Trait
                    return response($message, 200);
                }
            }
            /*  End if email exists then the condition applies  */

            // End Redis Authentication if data already existing in redis database 

            // Authentication Using Sql Database
            if ($emailInfo) {
                // Authenticating Password
                if (Hash::check($request->password, $emailInfo->password)) {
                    $token = $emailInfo->createToken('my-app-token')->plainTextToken;
                    $emailInfo->remember_token = $token;
                    $emailInfo->save();
                    $redis = Redis::connection();                   // Redis Connection

                    $this->redisStore($redis, $emailInfo, $request, $token);   // Trait for update Redis

                    Redis::expire('user:' . $emailInfo->id, 18000);     //EXPIRE KEY IN AFTER 5 HOURS
                    $key = 'last_activity_' . $emailInfo->id;               // Set last activity key 
                    Redis::set($key, Carbon::now());
                    $message = $this->tResponseSuccess($token, $email, $request);           // Response Message Using Trait
                    return response()->json($message, 200);
                } else {
                    $msg = "Incorrect Password";
                    $message = $this->tResponseFail($msg);               // Response Message Using Trait
                    return response($message, 200);
                }
            }
        }
        // Authentication Using Sql Database
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * -----------------------------------------------
     * @function function LogOut
     * -----------------------------------------------
     * Save null on remember_token in users table 
     * delete token
     * delete user key in redis database
     * @return message
     */
    public function logOut()
    {
        try {
            $id = auth()->user()->id;
            $user = User::where('id', $id)->first();
            $user->remember_token = null;
            $user->save();

            $user->currentAccessToken()->delete();

            Redis::connection();
            $redis = Redis::del(['user:' . $id, 'workflow_candidate:' . $id]);                              // Deleting Key from Redis Database  Deleting Workflow_candidate from Redis Database

            if ($redis) {
                return response()->json(['Token' => $user->remember_token ?? '', 'status' => $redis], 200);
            } else {
                return response()->json(['message' => 'Already Deleted'], 400);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * -----------------------------------------------
     * Parent @Controller- function changePass()
     * -----------------------------------------------
     * @param App\Http\Requests\Request 
     * @param App\Http\Requests\Request $request 
     * 
     * 
     */
    public function changePass(ChangePassRequest $request)
    {
        try {
            $id = auth()->user()->id;
            $user = User::find($id);
            $validPassword = Hash::check($request->password, $user->password);
            if ($validPassword) {

                $user->password = Hash::make($request->newPassword);
                $user->save();

                Redis::del('user:' . auth()->user()->id);   //DELETING REDIS KEY
                return responseMsgs(true, 'Successfully Changed the Password', "", "", "02", ".ms", "POST", $request->deviceId);
            }
            throw new Exception("Old Password dosen't Match!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "02", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Get All Users
     * | #uUlbID > Logged User Ulb ID
     * | #query > Query stmt for Ulb wise all Users and their Roles
     * | #users > All Users Data
     * | @var redis exta
     */
    public function getAllUsers()
    {
        $uUlbID = auth()->user()->ulb_id;
        $query = "SELECT 
                    u.id,
                    u.user_name,
                    u.mobile,
                    u.email,
                    u.user_type,
                    u.suspended,
                    u.super_user,
                    u.workflow_participant,
                    string_agg(ru.wf_role_id::VARCHAR,',') AS role_id,
                    string_agg(rm.role_name::VARCHAR,',') AS role_name
                    
                    FROM users u
                    LEFT JOIN wf_roleusermaps ru ON ru.user_id=u.id AND ru.is_suspended=FALSE
                    LEFT JOIN wf_roles rm ON rm.id=ru.wf_role_id
                    WHERE u.user_type!='Citizen' AND u.ulb_id=$uUlbID
                    AND u.suspended=false
                    GROUP BY u.id
                    ORDER BY u.id ASC";
        $users = DB::select($query);
        return responseMsg(true, "Data Fetched", $users);
    }

    /**
     * Get User by IDs
     * ----------------------------------------------------------------------------------------
     * @param user_id $id
     */
    public function getUser($id)
    {
        $user = DB::select("SELECT 
        u.id,
        u.user_name,
        u.mobile,
        u.email,
        u.user_type,
        u.suspended,
        u.super_user,
        u.workflow_participant,
        string_agg(ru.wf_role_id::VARCHAR,',') AS role_id,
        string_agg(rm.role_name::VARCHAR,',') AS role_name
        
        FROM users u
        LEFT JOIN wf_roleusermaps ru ON ru.user_id=u.id AND ru.is_suspended=FALSE
        LEFT JOIN wf_roles rm ON rm.id=ru.wf_role_id
        where u.id=$id
        AND u.suspended=false
        GROUP BY u.id ");
        if ($user) {
            return responseMsg(true, "Successfully Retrieved", $user);
        } else {
            return response()->json('User Not Available for this Id', 404);
        }
    }

    // Redis expiry, set, update testing
    public function testing()
    {
        // $user = auth()->user()->UserName;
        // $redis = Redis::set('UserName', $user);
        // return Redis::get('UserName');

        // Redis::pipeline(function ($pipe) {
        //     for ($i = 0; $i < 1000; $i++) {
        //         $pipe->set("key:$i", $i);
        //     }
        // });
        // $user = User::all();
        // $store = Redis::set('key', $user);
        Redis::connection();
        $store = Redis::get('user:' . auth()->user()->id);
        $manage = json_decode($store);
        return response()->json([
            'id' => $manage->id,
            'email' => $manage->email,
            'password' => $manage->password,
            'token' => $manage->remember_token,
            'created_at' => $manage->created_at,
            'updated_at' => $manage->updated_at
        ]);

        // if (Redis::del('user:' . auth()->user()->id)) {
        //     return response()->json('Deleted');
        // } else {
        //     return response()->json('already Deleted');
        // }

    }


    /**
     * --------------------------------------------------------------------------------------
     * My(user) Profiles 
     * --------------------------------------------------------------------------------------
     */

    /**
     * | For Showing Logged In User Details 
     * | #user_id= Get the id of current user 
     * | #redis= Find the details On Redis Server
     * | if $redis available then get the value from redis key
     * | if $redis not available then get the value from sql database
     */
    public function myProfileDetails()
    {
        $user_id = auth()->user()->id;
        $redis = Redis::get('user:' . $user_id);
        if ($redis) {
            $data = json_decode($redis);
            $collection = [
                'id' => $data->id,
                'name' => $data->name,
                'mobile' => $data->mobile,
                'email' => $data->email,
                'ulb_id' => $data->ulb_id
            ];
            $filtered = collect($collection);
            $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($filtered)];
            return $message;                                    // Filteration using Collection
        }
        if (!$redis) {
            $details = DB::select($this->query($user_id));
            $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($details[0])];
            return $message;
        }
    }


    /**
     * | Edit Citizen Profile
     * | @param Request $request 
     * | @return function update()
     */
    public function editMyProfile(Request $request)
    {
        $id = auth()->user()->id;
        $ulb = auth()->user()->ulb_id;
        $meta['id'] = $id;
        $meta['ulb'] = $ulb;
        $request->request->add($meta);
        return $this->update($request);
    }

    /**
     * |delete user 
     */
    public function deleteUser($request)
    {
        $data = User::find($request->id);
        $data->suspended = "true";
        $data->save();
        return responseMsg(true, "Data Deleted", '');
    }

    /**
     *  get employee list
     */
    public function employeeList()
    {
        $ulbId = authUser()->ulb_id;
        $data = User::select('user_name', 'id')
            ->where('user_type', 'Employee')
            ->where('ulb_id', $ulbId)
            ->orderBy('id')
            ->get();

        return responseMsg(true, "List Employee", $data);
    }

    /**
     * | Get user Notification
     */
    public function userNotification()
    {
        $user = authUser();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        $userType = $user->user_type;
        $mMirrorUserNotification = new MirrorUserNotification();
        if ($userType == 'Citizen') {
            $data = $mMirrorUserNotification->mirrorNotification()
                ->where('citizen_id', $userId)
                ->get();
            $notification = collect($data)->groupBy('category');
        } else
            $notification =  $mMirrorUserNotification->mirrorNotification($userId)
                ->where('user_id', $userId)
                ->where('ulb_id', $ulbId)
                ->get();

        if (collect($notification)->isEmpty())
            return responseMsgs(true, "No Current Notification", '', "010108", "1.0", "", "POST", "");

        return responseMsgs(true, "Your Notificationn", remove_null($notification), "010108", "1.0", "", "POST", "");
    }

    /**
     * | Add Notification
     */
    public function addNotification($req)
    {
        try {
            $user = authUser();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $citizenId = $req['citizenId'];
            $muserNotification = new UserNotification();
            $mMirrorUserNotification = new MirrorUserNotification();
            $addNotification = new AddNotification();                  #defining BLL

            $mreq = new Request([
                "user_id"       => $req['userId'] ?? null,
                "citizen_id"    => $req['citizenId'] ?? null,
                "notification"  => $req['notification'] ?? null,
                "send_by"       => $req['sender'] ?? null,
                "category"      => $req['category'] ?? null,
                "module_id"     => $req['moduleId'] ?? null,
                "event_id"      => $req['eventId'] ?? null,
                "ephameral"     => $req['ephameral'] ?? null,
                "require_acknowledgment" => $req['requireAcknowledgment'] ?? null,
                "sender_id"     => $userId ?? null,
                "ulb_id"        => $req['ulbId'] ?? null,
                "generation_time" => Carbon::now(),
                "expected_delivery_time" => null,
                "created_at" => Carbon::now(),
            ]);
            $id = $muserNotification->addNotification($mreq);
            $addNotification->notificationAddition($mreq, $id);           #calling BLL

            return responseMsgs(true, "Notificationn Addedd", '', "010108", "1.0", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), '', "010108", "1.0", "", "POST", "");
        }
    }

    /**
     * | Add Mirror Notification
     */
    public function addMirrorNotification($req, $id, $user)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mreq = new Request([
            "user_id" => $req->user_id,
            "citizen_id" => $req->citizen_id,
            "notification" => $req->notification,
            "send_by" => $req->send_by,
            "category" => $req->category,
            "sender_id" => $user->id,
            "ulb_id" => $user->ulb_id,
            "module_id" => $req->module_id,
            "event_id" => $req->event_id,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->require_acknowledgment,
            "expected_delivery_time" => $req->expected_delivery_time,
            "created_at" => Carbon::now(),
            "notification_id" => $id,
        ]);
        $mMirrorUserNotification->addNotification($mreq);
    }

    /**
     * | Update Mirror Notification
     */
    public function updateMirrorNotification($req, $id, $user)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mreq = new Request([
            "user_id" => $req->user_id,
            "citizen_id" => $req->citizen_id,
            "notification" => $req->notification,
            "send_by" => $req->send_by,
            "category" => $req->category,
            "sender_id" => $user->id,
            "ulb_id" => $user->ulb_id,
            "module_id" => $req->module_id,
            "event_id" => $req->event_id,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->require_acknowledgment,
            "expected_delivery_time" => $req->expected_delivery_time,
            "created_at" => Carbon::now(),
            "notification_id" => $id,
        ]);
        $mMirrorUserNotification->editNotification($mreq, 31);
    }

    /**
     * | Deactivate user Notification
     */
    public function deactivateNotification($req)
    {
        $muserNotification = new UserNotification();
        $muserNotification->deactivateNotification($req);

        return responseMsgs(true, "Notificationn Deactivated", '', "010108", "1.0", "", "POST", "");
    }
}

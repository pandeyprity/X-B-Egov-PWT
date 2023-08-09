<?php

namespace App\Models;

use App\Repository\Auth\EloquentAuthRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ActiveCitizen extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $guarded = [];
    // Citizen Registration
    public function citizenRegister($mCitizen, $request)
    {
        $mCitizen->user_name = $request->name;
        $mCitizen->email = $request->email;
        $mCitizen->mobile = $request->mobile;
        $mCitizen->password = Hash::make($request->password);
        $mCitizen->gender = $request->gender;
        $mCitizen->dob    = $request->dob;
        $mCitizen->aadhar = $request->aadhar;
        $mCitizen->is_specially_abled = $request->isSpeciallyAbled;
        $mCitizen->is_armed_force = $request->isArmedForce;
        // $mCitizen->aadhar_doc = $request->aadharDoc;
        // $mCitizen->specially_abled_doc = $request->speciallAbledDoc;
        // $mCitizen->armed_force_doc = $request->armedForceDoc;
        $mCitizen->ip_address = getClientIpAddress();
        $mCitizen->save();

        return $mCitizen->id;
    }

    /**
     * | Get Active Citizens by Moble No
     */
    public function getCitizenByMobile($mobile)
    {
        return ActiveCitizen::where('mobile', $mobile)
            ->first();
    }


    /**
     * | Change the Access Token in Case of Password Change 
     * | @param
     */
    public function changeToken($request)
    {
        $citizenInfo = ActiveCitizen::where('mobile', $request->mobileNo)
            ->first();

        if (isset($citizenInfo)) {
            $token['token'] = $citizenInfo->createToken('my-app-token')->plainTextToken;
            $citizenInfo->remember_token = $token['token'];
            $citizenInfo->save();
            return $token;
        }
    }

    /**
     * | Get Citizen according to id
     */
}

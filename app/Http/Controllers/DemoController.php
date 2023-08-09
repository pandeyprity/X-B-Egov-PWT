<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class DemoController extends Controller
{

    public function waterConnection(Request $req)
    {
        $data = User::select('application_no', 'ward_id')
            ->join('water_applications', 'water_applications.user_id', 'users.id')
            ->where('users.mobile', $req->mobileNo)
            ->get();
        return $data;
    }
}

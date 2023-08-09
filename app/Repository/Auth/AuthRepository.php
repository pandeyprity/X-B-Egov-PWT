<?php

namespace App\Repository\Auth;

use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;

interface AuthRepository
{
    public function store(AuthUserRequest $request);
    public function loginAuth(LoginUserRequest $request);
    public function logOut();
    public function changePass(ChangePassRequest $request);
}

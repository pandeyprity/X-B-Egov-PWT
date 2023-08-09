<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_auth';

    public function findToken($token)
    {
        return PersonalAccessToken::where('token', $token)
            ->first();
    }
}

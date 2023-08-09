<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleMaster extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function duesApi()
    {
        return ModuleMaster::orderby('id')->get();
    }
}

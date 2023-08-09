<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickAccessMaster extends Model
{
    use HasFactory;

    public function getList()
    {
        return QuickAccessMaster::where('status', true)
            ->get();
    }
}

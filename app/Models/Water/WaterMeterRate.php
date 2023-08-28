<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterMeterRate extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $connection = 'pgsql_water';
}

<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterDeactivatedDemand extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';
}

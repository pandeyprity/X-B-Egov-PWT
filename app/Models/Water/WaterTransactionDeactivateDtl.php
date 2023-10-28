<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTransactionDeactivateDtl extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $connection = 'pgsql_water';
}

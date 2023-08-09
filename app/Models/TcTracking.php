<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TcTracking extends Model
{
    use HasFactory;
    protected $fillable = ['lattitude', 'longitude', 'user_id'];
    public $timestamps = false;

    /**
     * |
     */
    public function store($req)
    {
        $req = $req->toarray();
        TcTracking::create($req);
    }

    /**
     * | 
     */
    public function getLocationByUserId($userId, $date)
    {
        return TcTracking::where('user_id', $userId)
            ->where('date', $date)
            ->where('status', true)
            ->orderby('id')
            ->get();
    }
}

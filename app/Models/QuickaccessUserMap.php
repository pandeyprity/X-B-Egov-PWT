<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickaccessUserMap extends Model
{
    use HasFactory;

    protected $fillable = ['quick_access_id', 'user_id', 'status'];

    public function getListbyUserId($userId)
    {
        return QuickaccessUserMap::where('status', true)
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * | Add Quick Access UserMap
     */
    public function store($request)
    {
        $request = $request->toarray();
        return QuickaccessUserMap::create($request);
    }

    /**
     * | Add Quick Access UserMap
     */
    public function edit($request)
    {
        $owner = QuickaccessUserMap::find($request->id);
        $request = $request->toarray();
        return $owner->update($request);
    }
}

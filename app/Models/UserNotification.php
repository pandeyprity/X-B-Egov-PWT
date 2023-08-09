<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserNotification extends Model
{
    use HasFactory;
    protected $updated_at = false;
    protected $fillable = [
        'user_id', 'user_type', 'citizen_id', 'category', 'notification', 'send_by', 'require_acknowledgment',
        'sender_id', 'ulb_id', 'module_id', 'event_id', 'generation_time', 'expected_delivery_time',
        'ephameral', 'created_at'
    ];

    /**
     * | Get notification of logged in user
     */
    public function userNotification()
    {
        return UserNotification::select('*', DB::raw("Replace(category, ' ', '_') AS category"))
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Add Notifications 
     */
    public function addNotification($req)
    {
        $req = $req->toarray();
        $notification =  UserNotification::create($req);
        return $notification->id;
    }

    /**
     * | deactivate Notifications 
     */
    public function deactivateNotification($req)
    {
        $notification = UserNotification::find($req->id);
        $notification->status = 0;
        $notification->save();
    }
}

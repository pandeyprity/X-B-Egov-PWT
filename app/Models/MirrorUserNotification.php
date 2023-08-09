<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MirrorUserNotification extends Model
{
    use HasFactory;
    protected $updated_at = false;
    protected $fillable = [
        'user_id', 'citizen_id', 'category', 'notification', 'send_by', 'require_acknowledgment',
        'sender_id', 'ulb_id', 'module_id', 'event_id', 'generation_time', 'expected_delivery_time',
        'ephameral', 'created_at', 'notification_id'
    ];

    /**
     * | Get notification of logged in user
     */
    public function mirrorNotification()
    {
        return MirrorUserNotification::select('*', DB::raw("Replace(category, ' ', '_') AS category"))
            ->where('status', 1)
            ->orderBy('id');
    }

    /**
     * | Add Notifications 
     */
    public function addNotification($req)
    {
        $req = $req->toarray();
        MirrorUserNotification::create($req);
    }

    /**
     * | Add Notifications 
     */
    public function editNotification($req, $id)
    {
        $req = $req->toarray();

        $notification = MirrorUserNotification::find($id);
        $notification->update($req);
    }
}

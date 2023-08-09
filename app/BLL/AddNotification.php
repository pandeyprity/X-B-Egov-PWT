<?php

namespace App\BLL;

use App\Models\MirrorUserNotification;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * | Add Notification
 * | Created By-Mrinal Kumar
 * | Created On-08-06-2023 
 * | Status: Open
 */

class AddNotification
{
    public function notificationAddition($req, $notificationId)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mUserNotification = new UserNotification();
        if (isset($req['citizen_id'])) {
            $userMirrorNotifications = $mMirrorUserNotification->mirrorNotification()
                ->where('citizen_id', $req['citizen_id'])
                ->get();
        } else
            $userMirrorNotifications = $mMirrorUserNotification->mirrorNotification()
                ->where('user_id', $req['user_id'])
                ->get();

        $userNotifications = $mUserNotification->userNotification()
            ->where('citizen_id', $req['citizen_id'])
            ->take(10)
            ->get();

        foreach ($userNotifications as $notification) {
            MirrorUserNotification::updateorinsert(
                ['notification_id'    => $notification->id],                 // Conditions to find the record
                [
                    "user_id"         => $notification->user_id,
                    "citizen_id"      => $notification->citizen_id,
                    "notification"    => $notification->notification,
                    "send_by"         => $notification->send_by,
                    "category"        => $notification->category,
                    "sender_id"       => $notification->user_id,
                    "ulb_id"          => $notification->ulb_id,
                    "module_id"       => $notification->module_id,
                    "event_id"        => $notification->event_id,
                    "ephameral"       => $notification->ephameral,
                    "notification_id" => $notification->id,
                    "generation_time" => $notification->generation_time,
                    "require_acknowledgment" => $notification->require_acknowledgment,
                    "expected_delivery_time" => $notification->expected_delivery_time,
                    "created_at"             => Carbon::now(),
                ]                                             // Data to update or create
            );
        }
    }

    /**
     * | Add Mirror Notification
     */
    public function addMirrorNotification($req, $notificationId)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mreq = new Request([
            "user_id" => $req->user_id,
            "citizen_id" => $req->citizen_id,
            "notification" => $req->notification,
            "send_by" => $req->send_by,
            "category" => $req->category,
            "sender_id" => $req->user_id,
            "ulb_id" => $req->ulb_id,
            "module_id" => $req->module_id,
            "event_id" => $req->event_id,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->require_acknowledgment,
            "expected_delivery_time" => $req->expected_delivery_time,
            "created_at" => Carbon::now(),
            "notification_id" => $notificationId,
        ]);
        $mMirrorUserNotification->addNotification($mreq);
    }

    // /**
    //  * | Update Mirror Notification
    //  */
    // public function updateMirrorNotification($req, $notificationId, $id)
    // {
    //     $mMirrorUserNotification = new MirrorUserNotification();
    //     $mreq = new Request([
    //         "user_id" => $req->user_id,
    //         "citizen_id" => $req->citizen_id,
    //         "notification" => $req->notification,
    //         "send_by" => $req->send_by,
    //         "category" => $req->category,
    //         "sender_id" => $req->user_id,
    //         "ulb_id" => $req->ulb_id,
    //         "module_id" => $req->module_id,
    //         "event_id" => $req->event_id,
    //         "generation_time" => Carbon::now(),
    //         "ephameral" => $req->ephameral,
    //         "require_acknowledgment" => $req->require_acknowledgment,
    //         "expected_delivery_time" => $req->expected_delivery_time,
    //         "created_at" => Carbon::now(),
    //         "notification_id" => $notificationId,
    //     ]);
    //     $mMirrorUserNotification->editNotification($mreq, $id);
    // }
}

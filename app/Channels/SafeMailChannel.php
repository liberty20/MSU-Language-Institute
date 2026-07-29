<?php

namespace App\Channels;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SafeMailChannel extends MailChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            return parent::send($notifiable, $notification);
        } catch (\Throwable $e) {
            Log::error("Mail notification failed to send: " . $e->getMessage(), [
                'exception' => $e,
                'notifiable' => is_object($notifiable) ? get_class($notifiable) . ' ID: ' . ($notifiable->id ?? 'unknown') : $notifiable,
                'notification' => get_class($notification)
            ]);
        }
    }
}

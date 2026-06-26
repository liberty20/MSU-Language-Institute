<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    protected $type;
    protected $title;
    protected $message;
    protected $actionUrl;
    protected $extraData;
    protected $triggerSource;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($type, $title, $message, $actionUrl = null, $extraData = [], $triggerSource = null)
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->extraData = $extraData;
        $this->triggerSource = $triggerSource;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $exists = $notifiable->unreadNotifications()
            ->where('data->type', $this->type)
            ->where('data->title', $this->title)
            ->where('data->message', $this->message)
            ->where('data->action_url', $this->actionUrl)
            ->exists();

        if ($exists) {
            return [];
        }

        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return array_merge([
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'trigger_source' => $this->triggerSource,
        ], $this->extraData);
    }

    /**
     * Send a notification only if an identical unread one does not already exist.
     */
    public static function sendUnique($user, $type, $title, $message, $actionUrl = null, $extraData = [], $triggerSource = null)
    {
        if (!$user) {
            return;
        }

        $exists = $user->unreadNotifications()
            ->where('data->type', $type)
            ->where('data->title', $title)
            ->where('data->message', $message)
            ->exists();

        if (!$exists) {
            $user->notify(new self($type, $title, $message, $actionUrl, $extraData, $triggerSource));
        }
    }
}

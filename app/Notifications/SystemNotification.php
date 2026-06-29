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
        $exists = $notifiable->unreadNotifications->contains(function ($notification) {
            $data = $notification->data;
            return is_array($data)
                && ($data['type'] ?? null) === $this->type
                && ($data['title'] ?? null) === $this->title
                && ($data['message'] ?? null) === $this->message
                && ($data['action_url'] ?? null) === $this->actionUrl;
        });

        if ($exists) {
            return [];
        }

        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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

        $exists = $user->unreadNotifications->contains(function ($notification) use ($type, $title, $message) {
            $data = $notification->data;
            return is_array($data)
                && ($data['type'] ?? null) === $type
                && ($data['title'] ?? null) === $title
                && ($data['message'] ?? null) === $message;
        });

        if (!$exists) {
            $user->notify(new self($type, $title, $message, $actionUrl, $extraData, $triggerSource));
        }
    }
}

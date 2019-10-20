<?php

namespace Puz\DynamicMail\Channels;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Puz\DynamicMail\DynMailer;
use Puz\DynamicMail\Messages\DynamicMailMessage;

class DynamicMailChannel extends MailChannel
{
    /**
     * The mailer implementation.
     *
     * @var \Illuminate\Contracts\Mail\Mailer|\Puz\DynamicMail\DynMailer
     */
    protected $mailer;

    /**
     * Create a new mail channel instance.
     *
     * @param \Puz\DynamicMail\DynMailer $mailer
     * @param \Illuminate\Mail\Markdown $markdown
     *
     * @return void
     */
    public function __construct(DynMailer $mailer, Markdown $markdown)
    {
        parent::__construct($mailer, $markdown);
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toDynamicMail($notifiable);

        if (!$notifiable->routeNotificationFor('dynamic-mail', $notification) &&
            !$message instanceof Mailable) {
            return;
        }

        if ($message instanceof Mailable) {
            return $message->send($this->mailer);
        }

        $mailer = $this->mailer;
        if ($message instanceof DynamicMailMessage) {
            if ($message->via) {
                $mailer = $mailer->via($message->via);
            }
            if ($message->driverConfig) {
                $mailer = $mailer->with($message->driverConfig);
            }
        }

        $mailer->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            $this->messageBuilder($notifiable, $notification, $message)
        );
    }

    /**
     * Get the recipients of the given message.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @param \Illuminate\Notifications\Messages\MailMessage $message
     *
     * @return mixed
     */
    protected function getRecipients($notifiable, $notification, $message)
    {
        if (is_string($recipients = $notifiable->routeNotificationFor('dynamic-mail', $notification))) {
            $recipients = [$recipients];
        }

        return collect($recipients)->mapWithKeys(function ($recipient, $email) {
            return is_numeric($email)
                ? [$email => (is_string($recipient) ? $recipient : $recipient->email)]
                : [$email => $recipient];
        })->all();
    }
}

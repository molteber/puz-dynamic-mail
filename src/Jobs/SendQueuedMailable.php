<?php

namespace Puz\DynamicMail\Jobs;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\SendQueuedMailable as IlluminateSendQueuedMailable;
use Puz\DynamicMail\Facades\DynamicMail;

class SendQueuedMailable extends IlluminateSendQueuedMailable
{
    /**
     * The mailable message instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailable|\Puz\DynamicMail\DynamicMailable
     */
    public $mailable;

    /**
     * @var string
     */
    public $via;

    /**
     * @var array
     */
    public $config;

    public function __construct(MailableContract $mailable, string $via, array $config)
    {
        parent::__construct($mailable);

        $this->via = $via;
        $this->config = $config;
    }

    /**
     * Handle the queued job.
     *
     * @param \Puz\DynamicMail\DynMailer|MailerContract $mailer
     *
     * @return void
     */
    public function handle(MailerContract $mailer)
    {
        /** @var \Puz\DynamicMail\DynMailer $mailer */
        $mailer = app('puz.dynamic-mail.mailer');
        if ($this->via) {
            $mailer = $mailer->via($this->via);
        }
        $mailer->with($this->config)->forceSend($this->mailable);
    }
}

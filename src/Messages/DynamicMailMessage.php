<?php

namespace Puz\DynamicMail\Messages;

use Illuminate\Notifications\Messages\MailMessage;

class DynamicMailMessage extends MailMessage
{
    /** @var string */
    public $via;

    /** @var array|null */
    public $driverConfig;

    public function via(string $via): self
    {
        $this->via = $via;

        return $this;
    }

    public function driverConfig(?array $config = null): self
    {
        $this->driverConfig = $config;

        return $this;
    }
}

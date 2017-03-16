<?php

namespace Puz\DynamicMail;

use Illuminate\Mail\Mailer as IlluminateMailer;

class Mailer extends IlluminateMailer
{

    public function withConfig($driver, array $config)
    {
        $newInstance = clone $this;

        /** @var \Illuminate\Support\Manager $manager */
        $manager = app('swift.transport');

        /** @var callable $customDriver */
        $customDriver = $manager->driver('dynamic_driver');

        /** @var \Swift_Transport $transporter */
        $transporter = $customDriver($driver, $config);

        $newInstance->setSwiftMailer(new \Swift_Mailer($transporter));

        return $newInstance;
    }
}

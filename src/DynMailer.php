<?php

namespace Puz\DynamicMail;

use Illuminate\Mail\Mailer as IlluminateMailer;

class DynMailer extends IlluminateMailer
{

    /**
     * Create a new instance of the mailer for given config
     *
     * @param string $driver
     * @param array  $config
     *
     * @return \Puz\DynamicMail\DynMailer
     */
    public function withConfig($driver, array $config = [])
    {
        $newInstance = clone $this;

        /** @var \Illuminate\Support\Manager $manager */
        $manager = app('puz.dynamic.transport');

        /** @var callable $customDriver */
        $customDriver = $manager->driver('puz.dynamic.driver');

        /** @var \Swift_Transport $transporter */
        $transporter = $customDriver($driver, $config);

        $newInstance->setSwiftMailer(new \Swift_Mailer($transporter));

        return $newInstance;
    }
}

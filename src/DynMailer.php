<?php

namespace Puz\DynamicMail;

use Illuminate\Mail\Mailer as IlluminateMailer;

class DynMailer extends IlluminateMailer
{
    protected $customDriver;

    /**
     * Create a new mailer instance based on driver (and config for given driver)
     *
     * @param string $driver
     * @param array  $config
     *
     * @return \Puz\DynamicMail\DynMailer
     */
    public function via($driver, array $config = [])
    {
        $newInstance = clone $this;

        $newInstance->customDriver = $driver;
        $newInstance->with($config);

        return $newInstance;
    }

    /**
     * Create new mailer instance and set config for current mailer driver
     *
     * @param array $config
     *
     * @return \Puz\DynamicMail\DynMailer
     */
    public function with(array $config)
    {
        $newInstance = clone $this;

        /** @var \Illuminate\Support\Manager $manager */
        $manager = app('puz.dynamic.transport');

        /** @var callable $customDriver */
        $customDriver = $manager->driver('puz.dynamic.driver');

        /** @var \Swift_Transport $transporter */
        $transporter = $customDriver($this->customDriver, $config);

        $newInstance->setSwiftMailer(new \Swift_Mailer($transporter));

        return $newInstance;

    }
}

<?php

namespace Puz\DynamicMail;

use Illuminate\Mail\Mailer as IlluminateMailer;

class DynMailer extends IlluminateMailer
{
    /** @var array An array containing the driver callback and name */
    protected $customDriver = [];

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

        $newInstance->prepareDriver();

        $newInstance->customDriver['name'] = $driver;
        $transporter = $newInstance->customDriver['callback']($newInstance->customDriver['name'], $config);

        $newInstance->setSwiftMailer(new \Swift_Mailer($transporter));

        return $newInstance;
    }

    /**
     * Sets a new Swift mailer instance and set config for current mailer driver
     *
     * @param array $config
     *
     * @return \Puz\DynamicMail\DynMailer
     */
    public function with(array $config)
    {
        if ($this->prepareDriver()) {
            $instance = clone $this;
        } else {
            $instance = $this;
        }

        /** @var \Swift_Transport $transporter */
        $transporter = $instance->customDriver['callback']($instance->customDriver['name'], $config);

        $instance->setSwiftMailer(new \Swift_Mailer($transporter));

        return $instance;
    }

    /**
     * Sets the customDriver property if not set via the "via" method.
     * This makes it possible to override config for default driver without the need to call "via" first.
     *
     * @return bool true if had to be prepared.
     */
    protected function prepareDriver()
    {
        if (empty($this->customDriver)) {

            /** @var \Illuminate\Support\Manager $manager */
            $manager = app('puz.dynamic.transport');

            /** @var callable $customDriver */
            $customDriver = $manager->driver('puz.dynamic.driver');

            $config = app('config')->get('mail');

            $this->customDriver = ['callback' => $customDriver, 'name' => $config['driver']];

            return true;
        }
        return false;
    }
}

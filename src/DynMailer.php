<?php

namespace Puz\DynamicMail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use InvalidArgumentException;
use Puz\DynamicMail\Jobs\SendQueuedMailable;

class DynMailer extends Mailer
{
    /** @var array An array containing the driver callback and name */
    protected $customDriver = [];

    public $via;

    public $withConfig;

    /**
     * Create a new mailer instance based on driver (and config for given driver)
     *
     * @param string $driver
     * @param array  $config
     *
     * @return \Puz\DynamicMail\DynMailer
     */
    public function via($driver, array $config = []): DynMailer
    {
        $newInstance = clone $this;

        $newInstance->prepareDriver();

        $newInstance->via = $driver;
        $newInstance->withConfig = $config;

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
    public function with(array $config): DynMailer
    {
        if ($this->prepareDriver()) {
            $instance = clone $this;
        } else {
            $instance = $this;
        }

        $instance->via = $instance->customDriver['name'];
        $instance->withConfig = $config;

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
    protected function prepareDriver(): bool
    {
        if (empty($this->customDriver)) {

            /** @var \Illuminate\Support\Manager $manager */
            $manager = app('puz.dynamic-mail.swift.transport');

            /** @var callable $customDriver */
            $customDriver = $manager->driver('puz.dynamic-mail.driver');

            $config = app('config')->get('mail');

            $this->customDriver = ['callback' => $customDriver, 'name' => $config['driver']];

            return true;
        }
        return false;
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array|MailableContract  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            $this->sendMailable($view);
            return;
        }

        parent::send($view, $data, $callback);
    }

        /**
     * Send a new message using a view.
     *
     * @param  string|array|MailableContract  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function forceSend($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            $this->forceSendMailable($view);
            return;
        }

        parent::send($view, $data, $callback);
    }

    /**
     * Send the given mailable.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return void
     */
    protected function forceSendMailable(MailableContract $mailable)
    {
        $mailable->send($this);
    }

    /**
     * Send the given mailable.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return mixed
     */
    protected function sendMailable(MailableContract $mailable)
    {
        return $mailable instanceof ShouldQueue
            ? $mailable->queueMailable($this->queue) : $mailable->send($this);
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable|\Puz\DynamicMail\DynamicMailable  $view
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function queue($view, $queue = null)
    {
        if (! $view instanceof DynamicMailable) {
            throw new InvalidArgumentException('Only DynamicMailable may be queued by DynamicMail.');
        }

        if (is_string($queue)) {
            $view->onQueue($queue);
        }

        return $view->queue($this->queue, $this);
    }

}



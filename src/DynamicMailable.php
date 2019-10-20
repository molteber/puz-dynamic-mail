<?php

namespace Puz\DynamicMail;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Mail\Mailable;
use Puz\DynamicMail\Jobs\SendQueuedMailable;

class DynamicMailable extends Mailable
{
    /**
     * Queue the message for sending.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param \Puz\DynamicMail\DynMailer|null $mailer
     * @return mixed
     */
    public function queue(Queue $queue, $mailer = null)
    {
        if (isset($this->delay)) {
            return $this->later($this->delay, $queue, $mailer);
        }

        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->pushOn(
            $queueName ?: null,
            new SendQueuedMailable($this, $mailer->via, $mailer->withConfig)
        );
    }

    /**
     * Deliver the queued message after the given delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param \Puz\DynamicMail\DynMailer|null $mailer
     * @return mixed
     */
    public function later($delay, Queue $queue, $mailer = null)
    {
        $connection = property_exists($this, 'connection') ? $this->connection : null;

        $queueName = property_exists($this, 'queue') ? $this->queue : null;

        return $queue->connection($connection)->laterOn(
            $queueName ?: null,
            $delay,
            new SendQueuedMailable($this, $mailer->via, $mailer->withConfig)
        );
    }

    /**
     * Render the mailable into a view.
     *
     * @return \Illuminate\View\View
     *
     * @throws \ReflectionException
     */
    public function render()
    {
        return $this->withLocale($this->locale, function () {
            Container::getInstance()->call([$this, 'build']);

            return Container::getInstance()->make('puz.dynamic-mail.mailer')->render(
                $this->buildView(), $this->buildViewData()
            );
        });
    }
}

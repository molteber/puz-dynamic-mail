<?php

namespace Puz\DynamicMail;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class DynamicMailServiceProvider extends IlluminateServiceProvider
{
    protected $defer = true;

    protected $drivers = [
        'smtp' => 'mail',
        'sendmail' => 'mail.sendmail',
        'ses' => 'services.ses',
        'mailgun' => 'services.mailgun',
        'mandrill' => 'services.mandrill',
    ];

    public function register()
    {
        $this->app->extend('mailer', function ($mailer, $app) {
            $config = $app->make('config')->get('mail');

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue']);
            }

            // Next we will set all of the global addresses on this mailer, which allows
            // for easy unification of all "from" addresses as well as easy debugging
            // of sent messages since they get be sent into a single email address.
            foreach (['from', 'reply_to', 'to'] as $type) {
                $this->setGlobalAddress($mailer, $config, $type);
            }

            return $mailer;
        });


        $this->app->extend('swift.transport', function ($transporter, $app) {
            return new TransportManager($app);
        });

        $this->app->alias('mailer', Mailer::class);
        $this->app->alias('swift.transport', TransportManager::class);
    }

    public function boot()
    {
        // Extend the transport manager
        /** @var \Puz\DynamicMail\TransportManager $transportManager */
        $transportManager = $this->app->make('swift.transport');
        $transportManager->extend('dynamic_driver', function ($app) use ($transportManager) {

            return function ($driver, $config) use ($app, $transportManager) {

                if (!array_key_exists($driver, $this->drivers)) {
                    return $transportManager->driver($driver);
                }
                $oldCallback = $transportManager->getDriverCallback($driver);
                $oldConfig = $app['config']->get($this->drivers[$driver]);

                $transportManager->resetDriverCallback($driver);

                $app['config']->set($this->drivers[$driver], $config);
                $transporter = $transportManager->driver($driver);

                $app['config']->set($this->drivers[$driver], $oldConfig);
                $transportManager->setDriverCallback($driver, $oldCallback);

                return $transporter;
            };
        });
    }

    /**
     * Set a global address on the mailer by type.
     *
     * @param  \Illuminate\Mail\Mailer  $mailer
     * @param  array  $config
     * @param  string  $type
     * @return void
     */
    protected function setGlobalAddress($mailer, array $config, $type)
    {
        $address = Arr::get($config, $type);

        if (is_array($address) && isset($address['address'])) {
            $mailer->{'always'.Str::studly($type)}($address['address'], $address['name']);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mailer', 'swift.transport',
        ];
    }
}

<?php

namespace Puz\DynamicMail;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Puz\DynamicMail\Channels\DynamicMailChannel;
use Swift_DependencyContainer;
use Swift_Mailer;

class DynamicMailServiceProvider extends MailServiceProvider
{
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__ . '/../config.php',
            'puz-dynamic-mail'
        );

        Notification::resolved(function (ChannelManager $service) {
            $service->extend('dynamic-mail', function ($app) {
                return new DynamicMailChannel($app->make('puz.dynamic-mail.mailer'), $app->make(Markdown::class));
            });
        });
    }

    protected function registerIlluminateMailer()
    {
        $this->registerDynamicTransport();

        $this->app->singleton('puz.dynamic-mail.mailer', function (Application $app) {
            $config = $app->make('config')->get('mail');

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $dynMailer = new DynMailer(
                $app['view'],
                $app['puz.dynamic-mail.swift.mailer'],
                $app['events']
            );

            if ($app->bound('queue')) {
                $dynMailer->setQueue($app['queue']);
            }

            // Next we will set all of the global addresses on this mailer, which allows
            // for easy unification of all "from" addresses as well as easy debugging
            // of sent messages since they get be sent into a single email address.
            foreach (['from', 'reply_to', 'to'] as $type) {
                $this->setGlobalAddress($dynMailer, $config, $type);
            }

            return $dynMailer;
        });
    }

    public function registerSwiftMailer()
    {
        $this->registerDynamicTransport();

        // Once we have the transporter registered, we will register the actual Swift
        // mailer instance, passing in the transport instances, which allows us to
        // override this transporter instances during app start-up if necessary.
        $this->app->singleton('puz.dynamic-mail.swift.mailer', function (Application $app) {
            if ($domain = $app->make('config')->get('mail.domain')) {
                Swift_DependencyContainer::getInstance()
                    ->register('mime.idgenerator.idright')
                    ->asValue($domain);
            }

            /** @var \Puz\DynamicMail\DynamicTransportManager $transport */
            $transport = $app['puz.dynamic-mail.swift.transport'];

            return new Swift_Mailer($transport->driver());
        });
    }

    /**
     * Register the Dynamic Transport instance.
     *
     * @return void
     */
    protected function registerDynamicTransport()
    {
        $this->app->singleton('puz.dynamic-mail.swift.transport', function ($app) {
            return new DynamicTransportManager($app);
        });
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config.php' => config_path('puz-dynamic-mailer.php'),
        ]);
        // Extend the transport manager
        /** @var \Puz\DynamicMail\DynamicTransportManager $transportManager */
        $transportManager = $this->app->make('puz.dynamic-mail.swift.transport');
        $transportManager->extend('puz.dynamic-mail.driver', function ($app) use ($transportManager) {
            return function ($driver, $config) use ($app, $transportManager) {
                $drivers = $app['config']->get('puz-dynamic-mail.drivers');
                if (!array_key_exists($driver, $drivers)) {
                    return $transportManager->driver($driver);
                }
                $oldCallback = $transportManager->getDriverCallback($driver);
                $oldConfig = $app['config']->get($drivers[$driver]);

                $transportManager->resetDriverCallback($driver);

                $config = array_merge($oldConfig, $config);
                $app['config']->set($drivers[$driver], $config);
                $transporter = $transportManager->driver($driver);

                $app['config']->set($drivers[$driver], $oldConfig);
                $transportManager->setDriverCallback($driver, $oldCallback);

                return $transporter;
            };
        });
    }

    protected function registerMarkdownRenderer()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'puz.dynamic-mail.mailer',
            'puz.dynamic-mail.swift.mailer',
            'puz.dynamic-mail.swift.transport',
        ];
    }
}

<?php

namespace Puz\DynamicMail\Facades;

use Illuminate\Support\Facades\Facade;

class DynamicMail extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'puz.dynamic.mailer';
    }
}

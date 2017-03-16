# Puz - Dynamic Mail
*A laravel package for changing email setting in runtime.*

***Note:** I have set the requirements to 5.4 because it's the latest version. Have not tried the others nor made tests yet. If you want to use this package for earlier versions of laravel, create a issue and i'll make it possible, if the technique works.*

## Install
1. `composer require puz/dynamic-mail`
2. Add `\Puz\DynamicMail\DynamicMailServiceProvider::class,` to your list of active providers.
3. You're almost ready!

## How to use it
Normally you use either the `Mail` facade or load a `$mailer` object. What i have done is to extend the current mailer by adding a custom method: `withConfig(string $driver, array $config = [])`.

Examples:
```php
<?php
// ...
Mail::withConfig('mailgun', [
    'domain' => 'my-customers-domain@domain.tld',
    'secret' => 'their-secret-api-key'
])->to($user)->send(new Holy\Mail);
```
By using the withConfig method, it creates a new instance of the mailer, including a new swift mailer and swift transporter. By doing this, i preserve the default mail settings so you can use the methods without setting any config.

You just simply give it the driver (which must be supported) and the configuration you would need to set for the current driver.

I however do not think this is a perfect solution, so i gladly accept any other suggestions which may be better.

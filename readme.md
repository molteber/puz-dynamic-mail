# Puz - Dynamic Mail
*A laravel package for changing email setting in runtime.*

***Note:** I have set the requirements to 5.4 because it's the latest version. Have not tried the others nor made tests yet. If you want to use this package for earlier versions of laravel, create a issue and i'll make it possible, if the technique works.*

## Install
1. `composer require puz/dynamic-mail`
2. Add `\Puz\DynamicMail\DynamicMailServiceProvider::class,` to your list of active providers.
3. Add the facade to your list of aliases. You can either overwrite Laravels own facade (no worries, this package only extend the functionality) or you can add a new one specifically for dynamic mail configuration:
```php
<?php
// ...
return [
    // ...
    'aliases' => [
        // ...
        // Overwrite Laravel mailer
        'Mail' => Puz\DynamicMail\Facades\DynamicMail::class,
        // Own mailer
        'DynamicMail' => Puz\DynamicMail\Facades\DynamicMail::class,
    ]
];
```
4. You're almost ready!

## How to use it
*In the examples I have overwritten Laravel mailer facade with this packages facade.*

Lets say you have these 3 situations:
1. You use the mailgun driver, but need to send from another domain
2. You use the smtp driver but need to change to a different driver which settings is already defined in `config/services.php`
3. You use the smtp driver but need to change to another service which is not defined.

Here is how to do so!
```php
<?php
// 1
Mail::with(['domain' => 'another.domain.tld'])->to('..')->send('..');

// 2
Mail::via('mailgun')->to('..')->send('..');

// 3
Mail::via('mailgun')->with(['domain' => 'hello.tld', 'secret' => 'https://www.youtube.com/watch?v=Iz-8CSa9xj8'])->to('..')->send('..');
```

## Short about the methods:
**via** method allows you to change the driver. Each time you use the **via** method, it creates a new instance of the mailer. This is so you can still use the default mailer if you'ld like. It takes a the driver name (string) as first argument, but allows you to set the config directly (as with **with**) in the second argument.

**with** is the one to set the configuration. It takes an array and takes the same array as you would do it in `config/services.php`.

## Supported drivers
I did set it to only allow some of the integrated mail services you can use with Laravel.
These are as follow:
* smtp
* sendmail
* ses
* mailgun
* mandrill

php-oauth-lib
=============
php-oauth-lib provides OAuth support in PHP 5.4+ and is very easy to integrate with any project which requires an OAuth client.

[![Build Status](https://travis-ci.org/Lusitanian/PHPoAuthLib.png?branch=master)](https://travis-ci.org/Lusitanian/PHPoAuthLib)
[![Code Coverage](https://scrutinizer-ci.com/g/Lusitanian/PHPoAuthLib/badges/coverage.png?s=a0a15bebfda49e79f9ce289b00c6dfebd18fc98e)](https://scrutinizer-ci.com/g/Lusitanian/PHPoAuthLib/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Lusitanian/PHPoAuthLib/badges/quality-score.png?s=c5976d2fefceb501f0d886c1a5bf087e69b44533)](https://scrutinizer-ci.com/g/Lusitanian/PHPoAuthLib/)
[![Latest Stable Version](https://poser.pugx.org/lusitanian/oauth/v/stable.png)](https://packagist.org/packages/lusitanian/oauth)
[![Total Downloads](https://poser.pugx.org/lusitanian/oauth/downloads.png)](https://packagist.org/packages/lusitanian/oauth)

Installation
------------
This library can be found on [Packagist](https://packagist.org/packages/pasadinhas/oauth).
The recommended way to install this is through [composer](http://getcomposer.org).

Edit your `composer.json` and add:

```json
{
    "require": {
        "pasadinhas/oauth": "~1.0.0"
    }
}
```

And then update your dependencies with:

```bash
$ composer update
```

Features
--------
- PSR-0 compliant for easy interoperability
- Fully extensible in every facet.
    - You can implement any service with any custom requirements by extending the protocol version's `AbstractService` implementation.
    - You can use any HTTP client you desire, just create a class utilizing it which implements `OAuth\Common\Http\ClientInterface` (two implementations are included)
    - You can use any storage mechanism for tokens. By default, session, in-memory and Redis.io (requires PHPRedis) storage mechanisms are included. Implement additional mechanisms by implementing `OAuth\Common\Token\TokenStorageInterface`.

Extend this package
-------------------
If you implement any new Service, HTTP Client or Storage, make a pull request so I can add it to this package. Don't forget to write some tests for it!

### Services
You can implement any service with custim requirements by extending and implementing the `AbstractService` class of the corresponding OAuth version. You will need to implement the abstract methods and should be ready to go! In order to parse the token response from OAuth2 it's recommended to use `TokenParserTrait` provided.
### HTTP Clients
You can implement any HTTP Client you desire. Just create a class that implements `OAuth\Common\Http\ClientInterface`. By default a `CurlClient` and a `StreamClient` are provided.
### Storage
The same for storage. Just implement the `OAuth\Common\Token\TokenStorageInterface` and use it in your app.

Service support
---------------
The library supports both OAuth 1.x and OAuth 2.0 compliant services. A list of currently implemented services can be found below.

Included service implementations
--------------------------------
- OAuth1
    - BitBucket
    - Etsy
    - FitBit
    - Flickr
    - Scoop.it!
    - Tumblr
    - Twitter
    - Xing
    - Yahoo
- OAuth2
    - Amazon
    - BitLy
    - Box
    - Dailymotion
    - Dropbox
    - Facebook
    - FenixEdu
    - Foursquare
    - GitHub
    - Google
    - Harvest
    - Heroku
    - Instagram
    - LinkedIn
    - Mailchimp
    - Microsoft
    - PayPal
    - Pocket
    - Reddit
    - RunKeeper
    - SoundCloud
    - Vkontakte
    - Yammer
- more to come! (Make pull requests if you have any!)

Examples
--------
Examples of basic usage are located in the [examples/](https://github.com/pasadinhas/oauth/master/tree/examples/) directory.

Usage
------
For usage with complete auth flow, please see the [examples](https://github.com/pasadinhas/oauth/master/tree/examples/).

Framework Integration
---------------------
* Laravel 4: I have [package](https://github.com/artdarek/oauth-4-laravel) for the library. It has a Service Provider which makes using this package even easier!

Tests
------
To run the tests, you must install dependencies with `composer install --dev`

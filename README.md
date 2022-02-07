# Silverstripe Sentry Handler

Minimalistic module designed to make it easier to obtain a `Sentry\Monolog\Handler` instance for consumption by
Monolog loggers.

⚠️ **If you simply want errors logged to Sentry, you’re probably better off using the
[`phptek/sentry`](https://github.com/phptek/silverstripe-sentry) module instead of this one.**

This module differs in that its goal is to make it easier to use Sentry with multiple Logger instances that have
different configuration options.

## Installation

`composer require bigfork/silverstripe-sentry-handler`

## Configuration

Simply add a `SENTRY_DSN` environment variable containing the DSN provided in the Sentry UI.

## Customisation

By default, this module will push an additional handler to the default `Psr\Log\LoggerInterface.errorhandler` service
which will push errors to Sentry (similar to the `phptek/sentry` module). You can disable this behaviour with:

```yml
SilverStripe\Core\Injector\Injector:
  Psr\Log\LoggerInterface.errorhandler:
    calls:
      pushSentryErrorHandler: null
```

You can configure another `Sentry\Monolog\Handler` instance by using the `SentryHubFactory` class to help build out
your Sentry hub to be passed the handler:

```yml
SilverStripe\Core\Injector\Injector:
  # Build a custom Hub object which holds our Sentry config, will be passed to the handler below
  MySentryHub:
    factory: 'Bigfork\SilverstripeSentryHandler\SentryHubFactory'
      constructor:
        options:
          dsn: '`SENTRY_DSN`'
          tags:
            - 'sometag'
          default_integrations: false
          integrations:
            - '%$MyCustomIntegrationClass'

  # Build Sentry\Monolog\Handler instance, to be pushed to logger above
  MySentryMonologHandler:
    class: 'Sentry\Monolog\Handler'
    constructor:
      - '%$MySentryHub' # Our custom hub object defined above
      - 'info' # Send anything logged at "info" level or above

  # Finally, build the logger service - access with Injector::inst()->get('MyMonologLogger')
  MyMonologLogger:
    type: 'singleton'
    class: 'Monolog\Logger'
    constructor:
      - 'myloggername'
    calls:
      pushSentryHandler: [ pushHandler, ['%$MySentryMonologHandler'] ] # Handler instance defined above
```

# TraceId setup
With traceId mode it's possible to configure the request/response/httpclient headers, and configure custom ID generators.

The bundle will try and use either Symfony Uid or Ramsey Uuid to generate the trace ids:
```shell
composer require ramsey/uuid
# OR
composer require symfony/uid
```
Instead it's also possible to configure a custom ID generator using the `generatorService` option.

## Configuration

```php
# /config/packages/symfony-trace-bundle.php
<?php
declare(strict_types=1);

use DR\SymfonyTraceBundle\Generator\TraceId\RamseyUuid4Generator;
use DR\SymfonyTraceBundle\TraceStorage;
use Sentry\State\HubInterface;
use Symfony\Config\SentryConfig;
use Symfony\Config\SymfonyTraceConfig;

return static function (SymfonyTraceConfig $config, ?SentryConfig $sentry): void {
    $config->traceMode('traceId');

    // Whether to trust the incoming request header. This is turned
    // on by default. If true a value in the `X-Trace-Id` header in the request
    // will be used as the trace ID for the rest of the request. If false
    // those values are ignored.
    $config->request()
        ->trustHeader(true)
         // Only trust the header from these IP's
        // trustedIps can be a comma or pipe char separated string or an array of IPs
        ->trustedIps(env('TRUSTED_IPS'));

    // Whether to send the trace details in the response headers. This is turned on by default.
    $config->response()
        ->sendHeader(true)
        // Only send the header to these IP's
        // trustedIps can be a comma or pipe char separated string or an array of IPs
        ->trustedIps(env('TRUSTED_IPS'));

    $config->traceid()
        // The header which the bundle inspects for the incoming trace ID
        // if this is not set an ID will be generated and set at this header
        ->requestHeader('X-Trace-Id')
        // The header which the bundle will set the trace ID to on the response
        ->responseHeader('X-Trace-Id')
        // The service key of an object that implements
        // DR\SymfonyTraceBundle\Generator\IdGeneratorInterface
        // Optional, will default to Symfony's Uuid or Ramsey's Uuid.
        ->generatorService(RamseyUuid4Generator::class);

    // The service key of an object that implements
    // DR\SymfonyTraceBundle\TraceStorageInterface
    $config->storageService(TraceStorage::class);

    // Whether to add the monolog process, defaults to true
    $config->enableMonolog(true);
    
    // Whether to add the request id to console commands, defaults to true
    $config->enableConsole(true);
    
    // Whether to add the request id to message bus events, defaults to false
    $config->enableMessenger(false);
    
    // Whether to add the twig extension, defaults to true
    $config->enableTwig(true);
    
    // Whether to pass traceId to outgoing http requests, defaults to false
    $config->httpClient()
        ->enabled(true)
        ->tagDefaultClient(false)
        // The header which the bundle will set the trace ID to on the outgoing request
        ->header('X-Trace-Id');
        
    // Whether to enable passing trace and transaction id to Sentry. Defaults to false.        
    $config->sentry()
        ->enabled(true)
        ->hubService(HubInterface::class);
    // disable sentry's own tracing
    $sentry?->tracing()?->enabled(false);
};
```

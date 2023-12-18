# TraceContext setup
With the default traceContext setup, tracing will be configured according to the [W3C TraceContext](https://www.w3.org/TR/trace-context/) specification.  
Incoming request data will be taken from the traceparent/tracestate headers, this data will be updated and passed to messenger and httpclient requests.

## Configuration

```php
# /config/packages/symfony-trace-bundle.php
<?php
declare(strict_types=1);

use DR\SymfonyTraceBundle\Generator\TraceId\RamseyUuid4Generator;
use DR\SymfonyTraceBundle\TraceStorage;
use Symfony\Config\SymfonyTraceConfig;

return static function (SymfonyTraceConfig $config): void {
    // Whether to trust the incoming request header. This is turned on by default.
    // If true a value in the `traceparent` header in the request
    // will be used and parsed to get the trace ID for the rest of the request. If false
    // those values are ignored and new trace ID's are generated.
    $config->trustRequestHeader(true);

    // Whether to send the trace details in the response headers. This is turned on by default.
    $config->sendResponseHeader(true);

    // The service key of an object that implements
    // DR\SymfonyTraceBundle\TraceStorageInterface
    // Defaults to TraceStorage::class
    $config->storageService(TraceStorage::class);

    // Whether to add the monolog process, defaults to true
    $config->enableMonolog(true);
    
    // Whether to add the request id to console commands, defaults to true
    $config->enableConsole(true);
    
    // Whether to add the request id to message bus events, defaults to false
    $config->enableMessenger(false);
    
    // Whether to add the twig extension, defaults to true
    $config->enableTwig(true);
    
    // Whether to pass traceparent & tracestate to outgoing http requests, defaults to false
    $config->httpClient()
        ->enabled(true)
        ->tagDefaultClient(false);
};
```

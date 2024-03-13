<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

// PHPUnit 11 compatibility. Symfony does not unregister error handlers (yet)
ErrorHandler::register(null, false);

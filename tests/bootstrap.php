<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Filesystem\Filesystem;

(new Filesystem())->remove(dirname(__DIR__) . '/tmp');

// PHPUnit 11 compatibility. Symfony does not unregister error handlers (yet)
ErrorHandler::register(null, false);

<?php

namespace Alighorbani\CommandManager\Exceptions;

use Throwable;
use Exception;

final class CustomExceptionHandler
{
    public static function handle(Throwable|Exception $th): void
    {
        $exceptionHandler = config('command-manager.exception-handler');

        if (!empty($exceptionHandler)) {
            $exceptionHandler($th);
        }

        // no ones know this exception throw!
    }
}

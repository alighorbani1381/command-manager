<?php

namespace Alighorbani\CommandManager\Exceptions;

use Exception;

class BadCommandCallException extends Exception
{
    public function __construct($signature)
    {
        $message = sprintf("The Command with signature %s doesn't registered in laravel kernel!", $signature);

        parent::__construct($message);
    }
}

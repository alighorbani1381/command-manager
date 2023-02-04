<?php

namespace Alighorbani\CommandManager\Exceptions;

use Exception;
use Alighorbani\CommandManager\AutomaticCommand;

class NotAutomaticCommandException extends Exception
{
    public function __construct($command)
    {
        $message = sprintf(
            "The Command %s doesn't type of Automatic Command to fix this problem extend from %s",
            $command,
            AutomaticCommand::class
        );

        parent::__construct($message);
    }
}

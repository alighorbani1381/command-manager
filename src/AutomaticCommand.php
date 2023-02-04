<?php

namespace Alighorbani\CommandManager;

use Illuminate\Console\Command;

abstract class AutomaticCommand extends Command
{
    protected string $version = '1.0.0';

    protected bool $maintenanceMode = false;
}

<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use Alighorbani\CommandManager\AutomaticCommand;

class MaintenanceFakeCommand extends AutomaticCommand
{
    protected $signature = 'fake:maintenance';

    protected $description = 'Fake command — runs in maintenance mode';

    protected bool $maintenanceMode = true;

    protected bool $runInQueue = false;

    public function handle(): void
    {
    }
}

<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use Alighorbani\CommandManager\AutomaticCommand;

class DelayedFakeCommand extends AutomaticCommand
{
    protected $signature = 'fake:delayed';

    protected $description = 'Fake command — uses per-command delay';

    protected int $delay = 30;

    public function handle(): void
    {
    }
}

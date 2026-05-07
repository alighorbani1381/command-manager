<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use RuntimeException;
use Alighorbani\CommandManager\AutomaticCommand;

class FailingFakeCommand extends AutomaticCommand
{
    protected $signature = 'fake:failing';

    protected $description = 'Fake command — always throws';

    protected bool $runInQueue = false;

    public function handle(): void
    {
        throw new RuntimeException('boom');
    }
}

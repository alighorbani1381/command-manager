<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use Alighorbani\CommandManager\AutomaticCommand;

class QueuedFakeCommand extends AutomaticCommand
{
    public static int $ranCount = 0;

    protected $signature = 'fake:queued';

    protected $description = 'Fake command — queued by default';

    public function handle(): void
    {
        self::$ranCount++;
    }
}

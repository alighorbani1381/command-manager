<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use Alighorbani\CommandManager\AutomaticCommand;

class InlineFakeCommand extends AutomaticCommand
{
    public static int $ranCount = 0;

    protected $signature = 'fake:inline';

    protected $description = 'Fake command — runs inline';

    protected bool $runInQueue = false;

    public function handle(): void
    {
        self::$ranCount++;
    }
}

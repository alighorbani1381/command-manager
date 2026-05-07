<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use Alighorbani\CommandManager\AutomaticCommand;

class VersionedFakeCommand extends AutomaticCommand
{
    public static int $ranCount = 0;

    protected $signature = 'fake:versioned';

    protected $description = 'Fake command — version-bumped';

    protected string $version = '1.0.1';

    protected bool $runInQueue = false;

    public function handle(): void
    {
        self::$ranCount++;
    }
}

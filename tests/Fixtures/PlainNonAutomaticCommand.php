<?php

namespace Alighorbani\CommandManager\Tests\Fixtures;

use Illuminate\Console\Command;

class PlainNonAutomaticCommand extends Command
{
    protected $signature = 'fake:plain';

    protected $description = 'Plain command — does NOT extend AutomaticCommand';

    public function handle(): void
    {
    }
}

<?php

namespace Alighorbani\CommandManager\Console;

use Illuminate\Console\Command;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Models\ArtisanCommandChain;

class CommandManagerReset extends Command
{
    protected $signature = 'command_manager:reset';

    protected $description = 'Reset the Command Manager that like you installed for first time';

    public function handle()
    {
        ArtisanCommandChain::truncate();
        ArtisanCommand::truncate();

        $this->info('Command Manager Reset Successfully!');
    }
}

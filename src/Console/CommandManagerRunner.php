<?php

namespace Alighorbani\CommandManager\Console;

use Throwable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Alighorbani\CommandManager\CommandService;
use Alighorbani\CommandManager\MaintenanceMode;
use Illuminate\Support\Facades\Artisan;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Models\ArtisanCommandChain;
use Alighorbani\CommandManager\Exceptions\CustomExceptionHandler;

class CommandManagerRunner extends Command
{
    use CommandService;

    protected $signature = 'command_manager:execute';

    protected $description = "Run automatically commands that registered in command manager";

    public function handle()
    {
        // sure command manager is on
        if ($this->isAutomateCommandRunnerOff()) {
            $this->newLine();
            $this->info('Command Manager is off it can not running command automatically! ');
            return false;
        }

        $this->getAutomatedCommandsAndValidated()
            ->detectCommandsMustRun()
            ->runDetectedCommands();
    }

    /**
     * Running Commands in Pending Status
     * @throws Throwable
     */
    public function runDetectedCommands()
    {
        if (count($this->commandInQueue) == 0) {
            $this->info('Nothing to Run...');
            return;
        }

        $chain = ArtisanCommandChain::query()->create([
            'started_at' => Carbon::now()
        ]);

        foreach ($this->commandInQueue as $commandInQueue) {
            $commandInQueue['chain_id'] = $chain->id;
            $this->runCommandWithExceptionHandling($commandInQueue);
        }

        $chain->update(['finished_at' => Carbon::now()]);
    }

    private function runCommandWithExceptionHandling($commandInQueue)
    {
        if ($commandInQueue['maintenance-mode']) {
            MaintenanceMode::on();
        }

        // create a command log
        $signature = $commandInQueue['signature'];

        $commandLog = ArtisanCommand::query()->create([
            'command' => $commandInQueue['class'],
            'signature' => $signature,
            'chain_id' => $commandInQueue['chain_id'],
            'maintenance_mode' => $commandInQueue['maintenance-mode'] ? "On" : "Off",
            'status' => 'InProgress',
            'version' => $commandInQueue['version'],
            'started_at' => Carbon::now()
        ]);

        $timeStart = microtime(true);

        try {
            $description = $this->getDescriptionOfArtisanTask($commandInQueue['class']);
            $this->components->task($description, fn() => Artisan::call($signature));
            $commandLog->update(['status' => 'Successful']);
            $this->newLine(2);
        } catch (Throwable $th) {
            CustomExceptionHandler::handle($th);
            $commandLog->update(['status' => 'Failed']);
        } finally {
            MaintenanceMode::off();
            $commandLog->update([
                'execution_time' => microtime(true) - $timeStart,
                'finished_at' => Carbon::now()
            ]);
        }
    }

    private function getDescriptionOfArtisanTask($class): string
    {
        return sprintf("<fg=blue;options=bold>%s</>", 'Running Command ' . $class) . PHP_EOL;
    }
}

<?php

namespace Alighorbani\CommandManager\Console;

use Alighorbani\CommandManager\Exceptions\BadCommandCallException;
use Alighorbani\CommandManager\Exceptions\NotAutomaticCommandException;
use ReflectionException;
use Throwable;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Alighorbani\CommandManager\CommandService;
use Alighorbani\CommandManager\MaintenanceMode;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Models\ArtisanCommandChain;
use Alighorbani\CommandManager\Jobs\RunArtisanCommandJob;
use Alighorbani\CommandManager\Exceptions\CustomExceptionHandler;

class CommandManagerRunner extends Command
{
    use CommandService;

    public array $commandInQueue;
    protected $signature = 'command_manager:execute';

    protected $description = "Run automatically commands that registered in command manager";

    /**
     * @throws Throwable
     * @throws NotAutomaticCommandException
     * @throws ReflectionException
     * @throws BadCommandCallException
     */
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
    public function runDetectedCommands(): void
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

    private function runCommandWithExceptionHandling($commandInQueue): void
    {
        if ($commandInQueue['run-in-queue'] ?? true) {
            $this->dispatchCommandJob($commandInQueue);
            return;
        }

        $this->runCommandInline($commandInQueue);
    }

    private function dispatchCommandJob(array $commandInQueue): void
    {
        $signature = $commandInQueue['signature'];

        // Queued: row exists so the next deploy doesn't re-dispatch the same
        // command, but it isn't running yet. The job flips this to
        // InProgress when a worker actually picks it up.
        $commandLog = ArtisanCommand::query()->create([
            'command' => $commandInQueue['class'],
            'signature' => $signature,
            'chain_id' => $commandInQueue['chain_id'],
            'maintenance_mode' => $commandInQueue['maintenance-mode'] ? "On" : "Off",
            'status' => 'Queued',
            'version' => $commandInQueue['version'],
            'started_at' => Carbon::now(),
        ]);

        $configDelay = (int) config('command-manager.dispatch_delay_seconds', 0);
        $commandDelay = (int) ($commandInQueue['delay'] ?? 0);
        $delay = max($configDelay, $commandDelay);

        $job = new RunArtisanCommandJob(
            commandClass: $commandInQueue['class'],
            signature: $signature,
            maintenanceMode: (bool) $commandInQueue['maintenance-mode'],
            logId: (int) $commandLog->id,
        );

        if ($connection = config('command-manager.connection')) {
            $job->onConnection($connection);
        }

        if ($queue = config('command-manager.queue')) {
            $job->onQueue($queue);
        }

        if ($delay > 0) {
            $job->delay(now()->addSeconds($delay));
        }

        dispatch($job);

        $this->info(sprintf(
            'Dispatched: %s%s',
            $signature,
            $delay > 0 ? " (delay {$delay}s)" : ''
        ));
        $this->newLine();
    }

    private function runCommandInline(array $commandInQueue): void
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

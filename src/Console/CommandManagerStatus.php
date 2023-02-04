<?php

namespace Alighorbani\CommandManager\Console;

use Illuminate\Console\Command;
use Alighorbani\CommandManager\CommandService;
use Alighorbani\CommandManager\Models\ArtisanCommand;

class CommandManagerStatus extends Command
{
    use CommandService;

    const TABLE_COLUMNS = ['Number', 'Command', 'Signature', 'Version', 'Maintenance Mode', 'Status'];

    protected $signature = 'command_manager:status';

    protected $description = "Show the status of command manager";

    public function handle()
    {
        // sure command manager is on
        if ($this->isAutomateCommandRunnerOff()) {
            $this->newLine();
            $this->warn(
                'Command Manager is off it dose not run any command but by the way I show you which command runs'
            );
        }

        $this->getAutomatedCommandsAndValidated()
            ->detectCommandsMustRun();

        $tableRows = $this->getTableRows();

        if (!count($tableRows)) {
            $this->info('Nothing Found!');
            return;
        }

        $this->table(self::TABLE_COLUMNS, $tableRows);
    }

    private function getTableRows(): array
    {
        $tableRows = [];

        $commandsRanBefore = ArtisanCommand::query()->get();

        foreach ($commandsRanBefore as $key => $commandRanBefore) {
            $tableRows[] = [
                $key + 1,
                $commandRanBefore->command,
                $commandRanBefore->signature,
                $commandRanBefore->version,
                $commandRanBefore->maintenance_mode == 'On' ? 'Yes' : 'No',
                sprintf("Ran %s", $commandRanBefore->status),
            ];
        }

        $key = count($commandsRanBefore) + 1;

        foreach ($this->commandInQueue as $commandInQueue) {
            $var = $commandInQueue['maintenance-mode'] ? 'Yes' : 'No';
            $tableRows[] = [
                $this->getPendingFormat($key),
                $this->getPendingFormat($commandInQueue['class']),
                $this->getPendingFormat($commandInQueue['signature']),
                $this->getPendingFormat($commandInQueue['version']),
                $this->getPendingFormat($var),
                $this->getPendingFormat('Pending')
            ];
            $key++;
        }

        return $tableRows;
    }

    private function getPendingFormat($value): string
    {
        return sprintf("<fg=yellow;options=bold>%s</>", $value);
    }
}

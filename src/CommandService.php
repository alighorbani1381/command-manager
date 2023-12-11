<?php

namespace Alighorbani\CommandManager;

use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Facades\Artisan;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Exceptions\BadCommandCallException;
use Alighorbani\CommandManager\Exceptions\NotAutomaticCommandException;

/**
 * @property array $automaticCommands
 * @property array $commandInQueue
 */
trait CommandService
{
    const COMMAND_SIGNATURE_INDEX = 0;

    /**
     * Read all the automated command list and validating them
     * @throws BadCommandCallException
     * @throws ReflectionException
     * @throws NotAutomaticCommandException
     */
    protected function getAutomatedCommandsAndValidated(): static
    {
        $commands = config('command-manager.commands');

        $automaticCommands = [];

        foreach ($commands as $command) {
            $reflection = new ReflectionClass($command);
            $defaultPropertiesValue = $reflection->getDefaultProperties();
            $signature = $this->getSignatureWithoutArguments($defaultPropertiesValue['signature']);

            // sure command registered in the laravel kernel
            if (!array_key_exists($signature, Artisan::all())) {
                throw new BadCommandCallException($signature);
            }

            // sure type of commands is automatic command
            if (!in_array(AutomaticCommand::class, (array)class_parents($command))) {
                throw new NotAutomaticCommandException($command);
            }

            $automaticCommands[] = [
                'class' => $command,
                'version' => $defaultPropertiesValue['version'],
                'maintenance-mode' => $defaultPropertiesValue['maintenanceMode'],
                'signature' => $signature
            ];
        }

        $this->automaticCommands = $automaticCommands;

        return $this;
    }


    protected function getSignatureWithoutArguments(string $signature): string
    {
        if (!str_contains($signature, '{')) {
            return $signature;
        }

        $segments = explode(" ", $signature);

        return $segments[self::COMMAND_SIGNATURE_INDEX];
    }

    protected function detectCommandsMustRun(): static
    {
        $commandInQueue = [];

        foreach ($this->automaticCommands as $automaticCommand) {
            // sure this command doesn't run before
            if ($this->isRanBefore($automaticCommand['signature'], $automaticCommand['version'])) {
                continue;
            }

            $commandInQueue[] = $automaticCommand;
        }

        $this->commandInQueue = $commandInQueue;

        return $this;
    }

    private function isAutomateCommandRunnerOff(): bool
    {
        return !config('command-manager.is-active');
    }

    private function isRanBefore($signature, $currentVersion): bool|int
    {
        $latestCommandLog = ArtisanCommand::query()
            ->distinct('version')
            ->where('signature', $signature)
            ->orderByDesc('id')
            ->first();

        if (empty($latestCommandLog)) {
            return false;
        }

        if ($latestCommandLog->version == $currentVersion) {
            return true;
        }

        return version_compare($currentVersion, $latestCommandLog->version, 'lt');
    }
}

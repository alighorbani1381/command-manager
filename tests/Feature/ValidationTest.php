<?php

use Alighorbani\CommandManager\Exceptions\BadCommandCallException;
use Alighorbani\CommandManager\Exceptions\NotAutomaticCommandException;
use Alighorbani\CommandManager\Tests\Fixtures\InlineFakeCommand;
use Alighorbani\CommandManager\Tests\Fixtures\PlainNonAutomaticCommand;

it('throws BadCommandCallException when a command is not registered in the kernel', function () {
    config()->set('command-manager.commands', [InlineFakeCommand::class]);
    // intentionally NOT calling $this->registerCommand(...)

    $this->withoutMockingConsoleOutput();

    expect(fn () => $this->artisan('command_manager:execute'))
        ->toThrow(BadCommandCallException::class);
});

it('throws NotAutomaticCommandException for commands not extending AutomaticCommand', function () {
    $this->registerCommand(new PlainNonAutomaticCommand());
    config()->set('command-manager.commands', [PlainNonAutomaticCommand::class]);

    $this->withoutMockingConsoleOutput();

    expect(fn () => $this->artisan('command_manager:execute'))
        ->toThrow(NotAutomaticCommandException::class);
});

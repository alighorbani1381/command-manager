<?php

use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Tests\Fixtures\InlineFakeCommand;
use Alighorbani\CommandManager\Tests\Fixtures\VersionedFakeCommand;

beforeEach(function () {
    InlineFakeCommand::$ranCount = 0;
    VersionedFakeCommand::$ranCount = 0;

    $this->registerCommand(new InlineFakeCommand());
    $this->registerCommand(new VersionedFakeCommand());
});

it('skips a command that already ran at the same version', function () {
    config()->set('command-manager.commands', [InlineFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();
    $this->artisan('command_manager:execute')->assertSuccessful();

    expect(InlineFakeCommand::$ranCount)->toBe(1)
        ->and(ArtisanCommand::query()->where('signature', 'fake:inline')->count())->toBe(1);
});

it('runs again when the command version is bumped', function () {
    // Seed a prior run at the older version 1.0.0.
    ArtisanCommand::query()->create([
        'command' => VersionedFakeCommand::class,
        'signature' => 'fake:versioned',
        'chain_id' => 0,
        'maintenance_mode' => 'Off',
        'status' => 'Successful',
        'version' => '1.0.0',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    config()->set('command-manager.commands', [VersionedFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    expect(VersionedFakeCommand::$ranCount)->toBe(1);

    $logs = ArtisanCommand::query()->where('signature', 'fake:versioned')->orderBy('id')->get();
    expect($logs)->toHaveCount(2)
        ->and($logs[1]->version)->toBe('1.0.1')
        ->and($logs[1]->status)->toBe('Successful');
});

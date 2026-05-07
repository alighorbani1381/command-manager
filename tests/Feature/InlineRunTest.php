<?php

use Illuminate\Support\Facades\Queue;
use Alighorbani\CommandManager\Jobs\RunArtisanCommandJob;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Tests\Fixtures\InlineFakeCommand;
use Alighorbani\CommandManager\Tests\Fixtures\FailingFakeCommand;
use Alighorbani\CommandManager\Tests\Fixtures\MaintenanceFakeCommand;

beforeEach(function () {
    InlineFakeCommand::$ranCount = 0;

    $this->registerCommand(new InlineFakeCommand());
    $this->registerCommand(new FailingFakeCommand());
    $this->registerCommand(new MaintenanceFakeCommand());
});

it('runs the command synchronously when runInQueue is false', function () {
    Queue::fake();

    config()->set('command-manager.commands', [InlineFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    Queue::assertNotPushed(RunArtisanCommandJob::class);
    expect(InlineFakeCommand::$ranCount)->toBe(1);

    $log = ArtisanCommand::query()->where('signature', 'fake:inline')->firstOrFail();
    expect($log->status)->toBe('Successful')
        ->and($log->finished_at)->not->toBeNull()
        ->and($log->execution_time)->toBeGreaterThanOrEqual(0);
});

it('marks an inline command Failed and continues when it throws', function () {
    Queue::fake();

    config()->set('command-manager.commands', [
        FailingFakeCommand::class,
        InlineFakeCommand::class,
    ]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    $failedLog = ArtisanCommand::query()->where('signature', 'fake:failing')->firstOrFail();
    $okLog = ArtisanCommand::query()->where('signature', 'fake:inline')->firstOrFail();

    expect($failedLog->status)->toBe('Failed')
        ->and($okLog->status)->toBe('Successful')
        ->and(InlineFakeCommand::$ranCount)->toBe(1); // failure didn't block the next command
});

it('toggles maintenance mode on commands that opt in', function () {
    $on = 0;
    $off = 0;

    config()->set('command-manager.maintenance-mode.on', function () use (&$on) {
        $on++;
    });
    config()->set('command-manager.maintenance-mode.off', function () use (&$off) {
        $off++;
    });
    config()->set('command-manager.commands', [MaintenanceFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    expect($on)->toBe(1)
        ->and($off)->toBeGreaterThanOrEqual(1);

    $log = ArtisanCommand::query()->where('signature', 'fake:maintenance')->firstOrFail();
    expect($log->maintenance_mode)->toBe('On');
});

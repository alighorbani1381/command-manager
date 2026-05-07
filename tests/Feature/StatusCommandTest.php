<?php

use Illuminate\Support\Facades\Queue;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Tests\Fixtures\QueuedFakeCommand;

beforeEach(function () {
    QueuedFakeCommand::$ranCount = 0;
    $this->registerCommand(new QueuedFakeCommand());
});

it('shows pending state for commands that have never run', function () {
    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:status')
        ->expectsOutputToContain('Pending')
        ->assertSuccessful();
});

it('shows Queued (waiting) for a dispatched but not yet picked up command', function () {
    Queue::fake();

    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    $log = ArtisanCommand::query()->where('signature', 'fake:queued')->firstOrFail();
    expect($log->status)->toBe('Queued');

    $this->artisan('command_manager:status')
        ->expectsOutputToContain('Queued (waiting)')
        ->assertSuccessful();
});

it('shows Running for a command currently being processed', function () {
    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    ArtisanCommand::query()->create([
        'command' => QueuedFakeCommand::class,
        'signature' => 'fake:queued',
        'chain_id' => 0,
        'maintenance_mode' => 'Off',
        'status' => 'InProgress',
        'version' => '1.0.0',
        'started_at' => now(),
    ]);

    $this->artisan('command_manager:status')
        ->expectsOutputToContain('Running')
        ->assertSuccessful();
});

it('shows Ran Successful for completed commands', function () {
    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    ArtisanCommand::query()->create([
        'command' => QueuedFakeCommand::class,
        'signature' => 'fake:queued',
        'chain_id' => 0,
        'maintenance_mode' => 'Off',
        'status' => 'Successful',
        'version' => '1.0.0',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $this->artisan('command_manager:status')
        ->expectsOutputToContain('Ran Successful')
        ->assertSuccessful();
});

it('warns when command manager is turned off but still shows commands', function () {
    config()->set('command-manager.is-active', false);
    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:status')
        ->expectsOutputToContain('off')
        ->assertSuccessful();
});

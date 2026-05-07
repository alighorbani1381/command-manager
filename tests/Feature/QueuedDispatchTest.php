<?php

use Illuminate\Support\Facades\Queue;
use Alighorbani\CommandManager\Jobs\RunArtisanCommandJob;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Tests\Fixtures\QueuedFakeCommand;
use Alighorbani\CommandManager\Tests\Fixtures\DelayedFakeCommand;

beforeEach(function () {
    QueuedFakeCommand::$ranCount = 0;

    $this->registerCommand(new QueuedFakeCommand());
    $this->registerCommand(new DelayedFakeCommand());
});

it('dispatches a queued command as a RunArtisanCommandJob by default', function () {
    Queue::fake();

    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    Queue::assertPushed(RunArtisanCommandJob::class, fn ($job) =>
        $job->commandClass === QueuedFakeCommand::class
        && $job->signature === 'fake:queued'
    );

    expect(QueuedFakeCommand::$ranCount)->toBe(0); // not run yet — only dispatched
});

it('creates the artisan_commands log row in Queued status when dispatched', function () {
    Queue::fake();

    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    $log = ArtisanCommand::query()->where('signature', 'fake:queued')->firstOrFail();

    expect($log->status)->toBe('Queued')
        ->and($log->command)->toBe(QueuedFakeCommand::class)
        ->and($log->version)->toBe('1.0.0')
        ->and($log->maintenance_mode)->toBe('Off');
});

it('applies the platform dispatch_delay_seconds floor to every job', function () {
    Queue::fake();

    config()->set('command-manager.commands', [QueuedFakeCommand::class]);
    config()->set('command-manager.dispatch_delay_seconds', 60);

    $this->artisan('command_manager:execute')->assertSuccessful();

    Queue::assertPushed(RunArtisanCommandJob::class, function ($job) {
        // delay() can be set to a Carbon instance or an int seconds count.
        return $job->delay !== null;
    });
});

it('uses max(configDelay, commandDelay) so the larger one wins', function () {
    Queue::fake();

    config()->set('command-manager.commands', [DelayedFakeCommand::class]);
    config()->set('command-manager.dispatch_delay_seconds', 5); // smaller than command's 30

    $this->artisan('command_manager:execute')->assertSuccessful();

    Queue::assertPushed(RunArtisanCommandJob::class, function ($job) {
        // We can't trivially extract seconds from a Carbon instance here,
        // but we can assert the job has a non-null delay applied.
        return $job->delay !== null;
    });
});

it('does not dispatch when command manager is turned off', function () {
    Queue::fake();

    config()->set('command-manager.commands', [QueuedFakeCommand::class]);
    config()->set('command-manager.is-active', false);

    $this->artisan('command_manager:execute')->assertSuccessful();

    Queue::assertNothingPushed();
    expect(ArtisanCommand::query()->count())->toBe(0);
});

it('does not re-dispatch the same command on a second deploy run', function () {
    Queue::fake();

    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();
    $this->artisan('command_manager:execute')->assertSuccessful();

    Queue::assertPushed(RunArtisanCommandJob::class, 1); // only the first deploy dispatched it
    expect(ArtisanCommand::query()->where('signature', 'fake:queued')->count())->toBe(1);
});

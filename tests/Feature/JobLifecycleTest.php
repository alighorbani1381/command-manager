<?php

use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Support\Facades\Log;
use Alighorbani\CommandManager\Jobs\RunArtisanCommandJob;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Tests\Fixtures\QueuedFakeCommand;

beforeEach(function () {
    QueuedFakeCommand::$ranCount = 0;
    $this->registerCommand(new QueuedFakeCommand());
});

it('flips Queued -> InProgress -> Successful when the worker runs the job', function () {
    config()->set('queue.default', 'sync'); // sync queue runs the job immediately
    config()->set('command-manager.connection', 'sync');
    config()->set('command-manager.commands', [QueuedFakeCommand::class]);

    $this->artisan('command_manager:execute')->assertSuccessful();

    $log = ArtisanCommand::query()->where('signature', 'fake:queued')->firstOrFail();

    expect($log->status)->toBe('Successful')
        ->and($log->started_at)->not->toBeNull()
        ->and($log->finished_at)->not->toBeNull()
        ->and(QueuedFakeCommand::$ranCount)->toBe(1);
});

it('releases the job back to the queue when a stale worker has no class', function () {
    Log::spy();

    $log = ArtisanCommand::query()->create([
        'command' => 'App\\NotYetLoaded\\PhantomCommand',
        'signature' => 'phantom:command',
        'chain_id' => 0,
        'maintenance_mode' => 'Off',
        'status' => 'Queued',
        'version' => '1.0.0',
        'started_at' => now(),
    ]);

    $job = new RunArtisanCommandJob(
        commandClass: 'App\\NotYetLoaded\\PhantomCommand',
        signature: 'phantom:command',
        maintenanceMode: false,
        logId: (int) $log->id,
    );

    $queueJob = Mockery::mock(QueueJobContract::class);
    $queueJob->shouldReceive('release')->once()->with(60);
    $queueJob->shouldReceive('attempts')->andReturn(1);

    $job->setJob($queueJob);
    $job->handle();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn ($message, $context = []) => str_contains($message, 'stale worker'))
        ->once();

    // The log row must not be flipped to InProgress by a stale-worker bounce.
    $log->refresh();
    expect($log->status)->toBe('Queued');
});

it('respects the configured release_seconds when bouncing a stale-worker job', function () {
    Log::spy();
    config()->set('command-manager.release_seconds', 90);

    $job = new RunArtisanCommandJob(
        commandClass: 'App\\NotYetLoaded\\PhantomCommand',
        signature: 'phantom:command',
        maintenanceMode: false,
        logId: 0,
    );

    $queueJob = Mockery::mock(QueueJobContract::class);
    $queueJob->shouldReceive('release')->once()->with(90);
    $queueJob->shouldReceive('attempts')->andReturn(1);

    $job->setJob($queueJob);
    $job->handle();
});

it('uses the configured job_tries value (default 10)', function () {
    config()->set('command-manager.job_tries', null);

    $job = new RunArtisanCommandJob(
        commandClass: QueuedFakeCommand::class,
        signature: 'fake:queued',
        maintenanceMode: false,
        logId: 0,
    );

    expect($job->tries)->toBe(10);

    config()->set('command-manager.job_tries', 25);

    $jobWithCustom = new RunArtisanCommandJob(
        commandClass: QueuedFakeCommand::class,
        signature: 'fake:queued',
        maintenanceMode: false,
        logId: 0,
    );

    expect($jobWithCustom->tries)->toBe(25);
});

it('marks the log as Failed via failed() when all retries are exhausted', function () {
    $log = ArtisanCommand::query()->create([
        'command' => QueuedFakeCommand::class,
        'signature' => 'fake:queued',
        'chain_id' => 0,
        'maintenance_mode' => 'Off',
        'status' => 'InProgress',
        'version' => '1.0.0',
        'started_at' => now(),
    ]);

    $job = new RunArtisanCommandJob(
        commandClass: QueuedFakeCommand::class,
        signature: 'fake:queued',
        maintenanceMode: false,
        logId: (int) $log->id,
    );

    $job->failed(new RuntimeException('exhausted'));

    $log->refresh();
    expect($log->status)->toBe('Failed')
        ->and($log->finished_at)->not->toBeNull();
});

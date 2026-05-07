<?php

namespace Alighorbani\CommandManager\Tests;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Alighorbani\CommandManager\CommandManagerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../src/migration');
    }

    protected function getPackageProviders($app): array
    {
        return [CommandManagerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Default queue connection used by the package's job dispatch.
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('command-manager.connection', 'sync');
    }

    /**
     * Register a command instance with Laravel's Artisan kernel so
     * `Artisan::all()` can find it during validation.
     */
    protected function registerCommand(\Illuminate\Console\Command $command): void
    {
        $this->app[ConsoleKernel::class]->registerCommand($command);
    }
}

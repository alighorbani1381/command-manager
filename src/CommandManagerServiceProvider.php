<?php

namespace Alighorbani\CommandManager;

use Illuminate\Support\ServiceProvider;
use Alighorbani\CommandManager\Console\CommandManagerReset;
use Alighorbani\CommandManager\Console\CommandManagerStatus;
use Alighorbani\CommandManager\Console\CommandManagerRunner;

class CommandManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        // disable this registration when app doesn't run in console environment
        if (!app()->runningInConsole()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/migration');

        $this->mergeConfigFrom(__DIR__ . '/config.php', 'command-manager');

        $this->commands([
            CommandManagerStatus::class,
            CommandManagerRunner::class,
            CommandManagerReset::class,
        ]);
    }

    public function boot()
    {
        $this->publishes([__DIR__ . '/config.php' => config_path('command-manager.php')], 'command-manager');
    }
}

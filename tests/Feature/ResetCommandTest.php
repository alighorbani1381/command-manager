<?php

use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Models\ArtisanCommandChain;

it('truncates artisan_commands and artisan_command_chains', function () {
    $chain = ArtisanCommandChain::query()->create(['started_at' => now()]);

    ArtisanCommand::query()->create([
        'command' => 'App\\SomeCommand',
        'signature' => 'some:command',
        'chain_id' => $chain->id,
        'maintenance_mode' => 'Off',
        'status' => 'Successful',
        'version' => '1.0.0',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    expect(ArtisanCommand::query()->count())->toBe(1)
        ->and(ArtisanCommandChain::query()->count())->toBe(1);

    $this->artisan('command_manager:reset')
        ->expectsOutputToContain('Reset Successfully')
        ->assertSuccessful();

    expect(ArtisanCommand::query()->count())->toBe(0)
        ->and(ArtisanCommandChain::query()->count())->toBe(0);
});

### 👑 What's Command Manager?

it's a laravel package that help you to running chain of commands with call just single artisan command.

it's very useful for automation flow of normalizing data and other stuff needs to run command after any new deploy as easy as adding 
`php artisan command_manager:execute` command into your CI/CD pipelines.

###  ⬇️ Installation
You can install the package via composer:

``` bash
composer require alighorbani1381/command-manager
```
Then you must run migrations to command manager  add the tables that it needs to work with.
``` bash
php artisan migrate
```
You may also publish config file:
``` bash
php artisan vendor:publish --provider="Alighorbani\CommandManager\CommandManagerServiceProvider"
```
After running above command you can find file in your config folder command-manager.php

in this files array exist that you can add your command in the commands key inside it

### 🚀 Usage

To run command automatically we have 3 steps that must be doing if you don't do one of these steps commands manager throw an exception!

1- First you must make your command, and extended from AutomaticCommand if you don't extend Automatic Command see the exception when commands run
``` php
<?php

namespace App\Console\Command;

use Alighorbani/CommandManager/AutomaticComand;

class MyCommand exnteds AutomaticCommand
{
    protected $signature = 'my:command';
    
    protected $description = 'My command to normalize data';
    
    protected function handle()
    {
        // functionality implemented here 
    }
}
```
2- Register your command in the laravel console kernel!
it's very important because if you don't register your command in kernel laravels can't find this command to run it!
``` php
<?php

namespace App\Console;

use App\Console\Command\MyCommand;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        MyCommand::class // 👈️👈️👈️ Add This
    ];
    
    ...
    ...
    ...
    
}

```


3- Add your command into commands list that called automatically by command manager.

``` php
<?php

use App\Console\Command\MyCommand;

return [
    // config here
    // another config here
    'commands' => [
        MyCommand::class, // 👈️👈️👈️ Add This
    ]
];
```

Tada 🎉 your job is finished!

Now You can use the below actions to run command or managing it

To check your command is successfully registered on command manager you can  run ``php artisan command_manager:status
`` command it's check the registration  of command and if your registration has problem throw the exceptions that you fix it
### 💎 Commands

``` bash
php artisan command_manager:status
```

This command **Showing Status of Commands** in the table that you can see which commands pend to run and which commands already run before, it's similar php artisan migrate:status command.

---

``` bash
php artisan command_manager:execute
```

This command **Executing Commands** that you register in the commands key in array config file.

---

``` bash
php artisan command_manager:reset
```
if you need to reset your command manager (remove all the history of running command and detect all commands as a new command) you can run this command.

⚠️ Warning: if you run this command its remove all the history of running command in your system, command manager detect all the commands in the list of config file.

---

### 💡 Automatic Command Features
In this version of package we have these features that allow you to manage commands with more control.
- Queue mode (default)
- Per-command delay
- Maintenance mode
- Versioning

---

### 🚀 Queue Mode (default) and Faster Deploys

By default, every automatic command is **dispatched as a queued job** instead of being run inline inside the deploy container. This makes deploys finish fast — `command_manager:execute` just dispatches the jobs and returns. The actual work runs on your queue workers.

If you want a command to run synchronously inside the deploy container (the old behavior), set `runInQueue` to `false` on it:

``` php
class MyCommand extends AutomaticCommand
{
    protected $signature = 'my:command';

    protected bool $runInQueue = false; // 👈️ run inline, not queued

    protected function handle()
    {
        // ...
    }
}
```

#### Per-command delay

You can also delay a single command before its job becomes available on the queue:

``` php
class MyCommand extends AutomaticCommand
{
    protected $signature = 'my:command';

    protected int $delay = 30; // seconds

    protected function handle()
    {
        // ...
    }
}
```

#### ⚠️ Platform race condition (Laravel Cloud, etc.)

On platforms like **Laravel Cloud**, the deploy container is on the new release **before** the queue worker fleet has rolled over to the new release image. If `command_manager:execute` dispatches a job for a brand-new command during that window, an old worker can pick it up and crash with `Class not found`.

This package guards against that in two ways:

1. **Dispatch delay** — set `COMMAND_MANAGER_DISPATCH_DELAY` (e.g. `60`–`120`) so dispatched jobs are only available *after* the worker rollout window.
2. **Stale-worker guard** — if a stale worker still picks up the job, the job detects that the command class isn't loaded yet and releases itself back to the queue. It retries up to `COMMAND_MANAGER_JOB_TRIES` times, waiting `COMMAND_MANAGER_RELEASE_SECONDS` between attempts.

Recommended `.env` on cloud platforms:

```
COMMAND_MANAGER_DISPATCH_DELAY=60
COMMAND_MANAGER_RELEASE_SECONDS=60
COMMAND_MANAGER_JOB_TRIES=10
COMMAND_MANAGER_QUEUE=default
COMMAND_MANAGER_QUEUE_CONNECTION=redis
```

On a normal single-server setup you can leave `COMMAND_MANAGER_DISPATCH_DELAY=0`.

#### Command status lifecycle

When a command runs through the queue, its row in `artisan_commands` walks through these states:

| Status       | When                                                                                             |
|--------------|--------------------------------------------------------------------------------------------------|
| `Queued`     | Just dispatched, sitting in the queue (possibly delayed).                                        |
| `InProgress` | A worker has picked the job up and is running it.                                                |
| `Successful` | The command finished without error.                                                              |
| `Failed`     | The command threw, **or** all retries were exhausted (handled by the job's `failed()` callback). |

`command_manager:status` shows `Queued (waiting)` and `Running` for the in-flight states so you can tell them apart from finished runs.

> ⚠️ Upgrade note: this version adds a new `Queued` value to the `status` enum. After upgrading run `php artisan migrate` to apply the schema change. On non-MySQL databases the migration converts `status` to a string column (requires `doctrine/dbal` on Laravel ≤ 9).

---

### 😴💻️ Maintenance mode
Sometimes we have a command that will be applied special changes that need to turn off any database modification operation (INSERT, UPDATE, DELETE) and for this reason we must put system in Maintenance mode that we define in our system.

to use this feature you should follow these steps

#### 1- Register Maintenance Mode
in config/command-manager.php
``` php
return [

    //  here 👇️
    'maintenance-mode' => [
        'on' => fn() => 'turn on',
        'off' => fn() => 'turn off'
    ],
    
    ...

];
```

you add your functionality of turning on/off maintenance mode as a callable closure or array!

#### 2- Activating on your command
``` php
<?php

namespace App\Console\Command;

use Alighorbani/CommandManager/AutomaticComand;

class MyCommand exnteds AutomaticCommand
{
    protected $signature = 'my:command';
    
    protected $description = 'My command to normalize data';
    
    protected bool $maintenanceMode = true; // 👈️ add this
    
    protected function handle()
    {
        // functionality implemented here 
    }
}
```
all sets done  ✅ your command will be run in maintenance mode!

---

### 📚️ Versioning
Sometimes you register command and run it with command manager, after a while you need to run again this command (after bug fixed or improve command logic) but command manager don't detect this command as a new command in this situation you should use versioning feature that command manager supported!

to add version you must use semantic versioning if you don't know about version please read below link 👇️👇️

🔗 https://semver.org

be default all of new command has version 1.0.0 if you need to run again as a new version it's easy as overwrite property in your command class.

⚠️ to set new version please add a verison that number bigger than the newest version that already run before!

``` php
<?php

namespace App\Console\Command;

use Alighorbani/CommandManager/AutomaticComand;

class MyCommand exnteds AutomaticCommand
{
    protected $signature = 'my:command';
    
    protected $description = 'My command to normalize data';
    
    protected string $version = '1.0.1'; // 👈️ chagen from 1.0.0 to 1.0.1
    
    protected function handle()
    {
        // functionality implemented here 
    }
}
```

⚠️ Don't Change Signature of your command because command manager detect commands from their signature and if you change it
Command manager detect command as a new command even if ran before

---

### ❌ Exceptions
Command Manager has a some exceptions that you may see it when running one of these commands

`php artisan command_manager:status`

`php artisan command_manager:execute`

|          Exception           |                           Reason                           |
|:----------------------------:|:----------------------------------------------------------:|
|   BadCommandCallException    |      Doesn't register your command in laravel kernel!      |
| NotAutomaticCommandException | Your command doesn't extended from Automatic Command Class |


.

---

.
### 🏁 A little view of Product Backlog
- [ ] Showing chain of commands that run in the status list.
- [ ] Showing the execution time of command that Ran.
- [ ] Adding filters into command_manager:status to filter custom status.
- [ ] Ability of running command that failed in the last Ran.
- [ ] Add command `command_manager:purge` to purge useless command from project

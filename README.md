### 🤔 What is Command manager?

it's a package that help you to running chain of commands with call just single artisan command.

it's very useful for automation flow of normalizing data and other stuff needs to run command after any new deploy as easy as added 
`php artisan command_manager:execute` command into your CI/CD pipelines.

###  ⬇️ Installation
You can install the package via composer:

``` bash
composer require alighorbani1381/command-manager
```
Then you must run migrations to command manager add the tables that need to working with.
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

To run command automatically we have 3 steps that must be doing if you don't do one of this steps commands manager make you exception

1- First you must make your command, and it's extended from automatic command if you don't extend Automatic Command see the exception when commands run
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

use Modules\MNA\Commands\FixRatio;

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
### 💎 Useful Actions

``` bash
php artisan command_manager:execute
```

This command **Executing Commands** that you register in the commands key in array config file.

---

``` bash
php artisan command_manager:status
```

This command **Showing Status of Commands** in pretty list of table that you can see which commands pend to run and which commands Ran before, it's similar php artisan migrate:status command.

``` bash
+--------+---------------------------------+-----------+---------+------------------+----------------+
| Number | Command                         | Signature | Version | Maintenance Mode | Status         |
+--------+---------------------------------+-----------+---------+------------------+----------------+
| 1      | App\Console\Commands\HelloWorld | say:hello | 1.0.1   | Yes              | Ran Successful |
| 2      | Modules\MNA\Commands\FixRatio   | fix:ratio | 1.0.1   | Yes              | Ran Successful |
```
---

``` bash
php artisan command_manager:reset
```
if you need to reset your command manager (remove all the logs from database and )

⚠️ Warning: if you run this command its remove all the history of running command in your system, command manager detect all the commands in the list of  



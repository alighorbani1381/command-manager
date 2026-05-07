<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active/DeActive Command Manager
    |--------------------------------------------------------------------------
    |
    | If you add command command_manager:execute in your CI/CD Pipeline and you need to
    | disable command manager you can turn off command manager with this flag
    |
    */
    'is-active' => true,

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Here is where you can register your functionality to setting up
    | maintenance mode during commands run or turn off after command finished
    | here you can pass the closure or array with class and method to register
    |
    */
    'maintenance-mode' => [
        'on' => fn() => 'turn on',
        'off' => fn() => 'turn off'
    ],

    /*
   |--------------------------------------------------------------------------
   | Exception Handling
   |--------------------------------------------------------------------------
   |
   | When command failed we need to handle this exception that throw
   | you can get Throwable Object as a parameter in your closure and
   | implement exception handling (sending into third party service like sentry or etc.)
   |
   */
    'exception-handler' => function (Throwable $th) {
        if (function_exists('capture_exception')) {
            capture_exception($th);
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Automatic Commands List
    |--------------------------------------------------------------------------
    |
    | The list of commands that you want to run automatically after any deploy
    | Important note: you need to register command in laravel console kernel before
    | after that put your class reference command here!
    | also commands that you added here must Be extended from AutomaticCommand Class
    */
    'commands' => [
        // adding your automatic command here :)
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Race Condition (Queue + Delay)
    |--------------------------------------------------------------------------
    |
    | On platforms like Laravel Cloud, the deploy container runs the new
    | release immediately, but queue workers keep running the OLD release for
    | a short time until they roll over. If we dispatch a job for a brand-new
    | command during that window, an old worker may pick it up and crash with
    | "Class not found" because the new command class isn't in its image yet.
    |
    | We avoid this with two simple tools:
    |
    |   1) dispatch_delay_seconds — every dispatched job is delayed by this
    |      many seconds, so workers only see it AFTER they've rolled to the
    |      new release. Set it a bit higher than your worst rollout window
    |      (e.g. 60–120s) on cloud platforms. Keep it 0 for normal servers.
    |
    |   2) release_seconds + job_tries — if a stale worker still grabs the
    |      job, it detects the missing class and releases the job back to the
    |      queue (waiting `release_seconds`) so a fresh worker can run it.
    |      `job_tries` is high enough to survive a few release bounces.
    |
    | queue / connection let you pin command jobs to a specific queue so
    | they don't compete with normal app jobs.
    |
    */
    'dispatch_delay_seconds' => (int) env('COMMAND_MANAGER_DISPATCH_DELAY', 0),

    'release_seconds' => (int) env('COMMAND_MANAGER_RELEASE_SECONDS', 60),

    'job_tries' => (int) env('COMMAND_MANAGER_JOB_TRIES', 10),

    'queue' => env('COMMAND_MANAGER_QUEUE', 'default'),

    'connection' => env('COMMAND_MANAGER_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
];

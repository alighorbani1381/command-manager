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
    ]
];

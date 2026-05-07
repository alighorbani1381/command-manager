<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE artisan_commands MODIFY COLUMN status ENUM('Queued','InProgress','Successful','Failed') NOT NULL");

            return;
        }

        // For pgsql / sqlite / other drivers, convert the enum to a plain
        // string so the new "Queued" value is accepted without driver-specific
        // enum gymnastics. Requires doctrine/dbal on Laravel <= 9.
        Schema::table('artisan_commands', function ($table) {
            $table->string('status', 16)->change();
        });
    }

    public function down()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("UPDATE artisan_commands SET status = 'Failed' WHERE status = 'Queued'");
            DB::statement("ALTER TABLE artisan_commands MODIFY COLUMN status ENUM('InProgress','Successful','Failed') NOT NULL");

            return;
        }

        DB::statement("UPDATE artisan_commands SET status = 'Failed' WHERE status = 'Queued'");

        Schema::table('artisan_commands', function ($table) {
            $table->string('status', 16)->change();
        });
    }
};

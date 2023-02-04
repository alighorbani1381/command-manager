<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up()
    {
        Schema::create('artisan_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('signature');
            $table->string('version');
            $table->enum('maintenance_mode', ['On', 'Off']);
            $table->integer('chain_id');
            $table->float('execution_time')->nullable();
            $table->enum('status', ['InProgress', 'Successful', 'Failed']);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('artisan_commands');
    }
};

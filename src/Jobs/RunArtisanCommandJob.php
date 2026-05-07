<?php

namespace Alighorbani\CommandManager\Jobs;

use Throwable;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Alighorbani\CommandManager\MaintenanceMode;
use Alighorbani\CommandManager\Models\ArtisanCommand;
use Alighorbani\CommandManager\Exceptions\CustomExceptionHandler;

class RunArtisanCommandJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public function __construct(
        public readonly string $commandClass,
        public readonly string $signature,
        public readonly bool $maintenanceMode,
        public readonly int $logId,
    ) {
        $this->tries = (int) (config('command-manager.job_tries') ?: 10);
    }

    public function handle(): void
    {
        // Stale-worker guard: when the deploy container dispatches a job for
        // a newly-added command before the queue worker fleet has rolled to
        // the new release image, a previous-release worker can pick the job
        // up and fail to resolve the class. Release the job back to the
        // queue and let a fresh worker handle it after rollout completes.
        if (! class_exists($this->commandClass)) {
            Log::warning('CommandManager: stale worker, releasing', [
                'command' => $this->commandClass,
                'signature' => $this->signature,
                'attempt' => $this->attempts(),
            ]);

            $this->release((int) (config('command-manager.release_seconds') ?: 60));

            return;
        }

        $commandLog = ArtisanCommand::query()->find($this->logId);
        $commandLog?->update([
            'status' => 'InProgress',
            'started_at' => Carbon::now(),
        ]);

        if ($this->maintenanceMode) {
            MaintenanceMode::on();
        }

        $timeStart = microtime(true);

        try {
            Artisan::call($this->signature);
            $commandLog?->update(['status' => 'Successful']);
        } catch (Throwable $th) {
            CustomExceptionHandler::handle($th);
            $commandLog?->update(['status' => 'Failed']);
        } finally {
            MaintenanceMode::off();
            $commandLog?->update([
                'execution_time' => microtime(true) - $timeStart,
                'finished_at' => Carbon::now(),
            ]);
        }
    }

    public function failed(Throwable $th): void
    {
        CustomExceptionHandler::handle($th);

        ArtisanCommand::query()
            ->whereKey($this->logId)
            ->update([
                'status' => 'Failed',
                'finished_at' => Carbon::now(),
            ]);
    }
}

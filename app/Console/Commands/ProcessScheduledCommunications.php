<?php

// app/Console/Commands/ProcessScheduledCommunications.php
namespace App\Console\Commands;

use App\Models\Communication;
use App\Services\Communication\CommunicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledCommunications extends Command
{
    protected $signature = 'communications:process';
    protected $description = 'Dispatch scheduled and recurring communications that are due';

    public function handle(CommunicationService $service): int
    {
        Communication::query()
            ->where('status', 'scheduled')
            ->where('next_run_at', '<=', now())
            ->chunkById(50, fn ($due) => $due->each(fn ($comm) => $service->dispatchNow($comm)));

        return self::SUCCESS;
    }
}

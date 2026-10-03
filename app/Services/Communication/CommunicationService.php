<?php

// app/Services/Communication/CommunicationService.php
namespace App\Services\Communication;

use App\Jobs\SendCommunicationBatchJob;
use App\Models\Communication;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class CommunicationService
{
    public function create(array $data): Communication
    {
        $comm = Communication::create([
            ...$data,
            'status'      => filled($data['send_at'] ?? null) ? 'scheduled' : 'draft',
            'next_run_at' => $data['send_at'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        if (blank($data['send_at'] ?? null)) {
            $this->dispatchNow($comm);
        }

        return $comm;
    }

    public function dispatchNow(Communication $comm): void
    {
        $comm->update(['status' => 'sending']);

        $recipients = app(AudienceResolver::class)->resolve($comm);

        if ($recipients->isEmpty()) {
            $comm->update(['status' => 'failed']);
            return;
        }

        Log::info('Dispatching communication', [
            'communication_id' => $comm->id,
            'channel'          => $comm->channel,
            'recipient_count'  => $recipients->count(),
        ]);

        $jobs = $recipients
            ->chunk(config('communication.batch_size', 100))
            ->map(fn (Collection $chunk) => new SendCommunicationBatchJob($comm, $chunk->values()->all()))
            ->all();

        $id = $comm->id;

        Bus::batch($jobs)
            ->name("communication-{$id}")
            ->allowFailures()
            ->finally(fn (Batch $batch) => Communication::find($id)?->finalizeRun())
            ->dispatch();
    }

}

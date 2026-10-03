<?php

// app/Jobs/SendCommunicationBatchJob.php
namespace App\Jobs;

use App\Models\Communication;
use App\Models\CommunicationRecipient;
use App\Models\Setting;
use App\Services\Communication\GatewayResolver;
use App\Support\CommunicationRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;
use InvalidArgumentException;
use Throwable;

class SendCommunicationBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(protected Communication $communication, protected array $recipients) {}

    public function handle(GatewayResolver $resolver): void
{
    if ($this->batch()?->cancelled()) {
        return;
    }

    Log::info($this->providerFor($this->communication->channel) . ' batch job started', [
        'communication_id' => $this->communication->id,
        'recipient_count'  => count($this->recipients),
    ]);

    $comm     = $this->communication;
    $channel  = $comm->channel;
    $template = $comm->template;
    $isEmail  = $channel === 'email';
    $gateway  = $resolver->resolve($this->providerFor($channel));

    Log::info('Dispatching comms', [
        'gateway'  => $gateway,
        'channel'  => $channel,
        'is_email' => $isEmail,
        'templated' => $template !== null,
    ]);

    // Resolve once per batch, not per recipient
    $layout = $isEmail ? [
        'schoolName' => setting('school_name') ?? config('app.name'),
        'logoUrl'    => $this->resolveImageBase64(setting('logo_path')),
        'brandColor' => setting('primary_color') ?? '#0d6efd',
    ] : [];

    // Templated: use the template. Non-templated: use the communication's own details.
    $templateBody    = $template
        ? $this->bodyFor($channel, $template)
        : (string) ($comm->body ?? '');

    $templateSubject = $template
        ? (string) ($template->subject ?? $comm->subject ?? '')
        : (string) ($comm->subject ?? '');

    // Retry-safe: skip destinations already logged for this communication
    $done = CommunicationRecipient::where('communication_id', $comm->id)
        ->whereIn('destination', array_column($this->recipients, 'destination'))
        ->pluck('destination')
        ->all();

    foreach ($this->recipients as $r) {
        if (in_array($r['destination'], $done, true)) {
            continue;
        }

        $placeholders = $r['placeholders'] ?? [];
        $subject      = $isEmail ? CommunicationRenderer::render($templateSubject, $placeholders) : null;
        $body         = CommunicationRenderer::render($templateBody, $placeholders);

        try {
            if ($isEmail) {
                $body = view('emails.communication', ['bodyHtml' => $body] + $layout)->render();
            }

            $result = $gateway->send($r['destination'], $body, $subject);
        } catch (Throwable $e) {
            Log::warning('Communication send failed', [
                'communication_id' => $comm->id,
                'destination'      => $r['destination'],
                'error'            => $e->getMessage(),
            ]);
            $result = ['success' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        CommunicationRecipient::create([
            'communication_id'   => $comm->id,
            'recipient_type'     => $r['recipient_type'] ?? null,
            'recipient_id'       => $r['recipient_id'] ?? null,
            'destination'        => $r['destination'],
            'status'             => ($result['success'] ?? false) ? 'sent' : 'failed',
            'gateway_message_id' => $result['message_id'] ?? null,
            'error'              => $result['error'] ?? null,
            'sent_at'            => ($result['success'] ?? false) ? now() : null,
        ]);
    }
}

    private function providerFor(string $channel): string
    {
        return match ($channel) {
            'email'    => 'smtp',
            'sms'      => 'sms',
            'whatsapp' => 'whatsapp',
            default    => throw new InvalidArgumentException("Unsupported channel [{$channel}]"),
        };
    }

    private function bodyFor(string $channel, $template): string
    {
        return match ($channel) {
            'email'    => $template?->email_body,
            'sms'      => $template?->sms_body,
            'whatsapp' => $template?->whatsapp_body ?? $template?->sms_body,
            default    => null,
        } ?? '';
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path);

        if (! file_exists($fullPath)) {
            Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}

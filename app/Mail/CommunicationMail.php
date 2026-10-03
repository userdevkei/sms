<?php

// app/Mail/CommunicationMail.php
namespace App\Mail;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.communication')
            ->with([
                'bodyHtml'  => $this->bodyHtml,
                'schoolName' => setting('school_name') ?? config('app.name'),
                'logoUrl'    => $this->resolveImageBase64(setting('logo_path')) ?? null,
                'brandColor' => setting('brand_color') ?? '#0d6efd',
            ]);
    }

    private function resolveImageBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = base_path($path); // since path already starts with "Files/..."

        if (! file_exists($fullPath)) {
            \Log::info('Logo file not found', ['path' => $fullPath]);
            return null;
        }

        $mime = mime_content_type($fullPath);
        $data = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}

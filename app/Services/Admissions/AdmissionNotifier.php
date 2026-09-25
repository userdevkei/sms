<?php

namespace App\Services\Admissions;

use App\Models\AdmissionApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends SMS + email to the person who started the application.
 * A failed notification is logged and NEVER blocks the applicant's flow.
 */
class AdmissionNotifier
{
    public function sendCode(AdmissionApplication $application, string $code, ?string $onlyChannel = null): void
    {
        $school = config('app.name');
        $url    = route('apply.landing');

        $this->dispatch(
            $application,
            "Your {$school} application code",
            "Hello {$application->contact_name},\n\nYour application code is: {$code}\n\n"
            ."Use it any time at {$url} to continue your application or check its status.\n"
            ."Reference: {$application->reference}\n\nKeep this code private — anyone who has it can open the application.",
            "{$school} admissions: your application code is {$code}. Continue or check status at {$url}",
            $onlyChannel
        );
    }

    public function notify(AdmissionApplication $application, string $subject, string $message): void
    {
        $school = config('app.name');
        $url    = route('apply.landing');

        $this->dispatch(
            $application,
            $subject,
            "Hello {$application->contact_name},\n\n{$message}\n\nApplication reference: {$application->reference}\nCheck the full status any time at {$url} using your application code.",
            "{$school}: {$message} (Ref {$application->reference})"
        );
    }

    private function dispatch(AdmissionApplication $application, string $subject, string $emailBody, string $smsBody, ?string $onlyChannel = null): void
    {
        if ($application->contact_phone && $onlyChannel !== 'email') {
            $this->safely(fn () => $this->sendSms($application->contact_phone, $smsBody), 'sms', $application);
        }

        if ($application->contact_email && $onlyChannel !== 'sms') {
            $this->safely(fn () => $this->sendEmail($application->contact_email, $subject, $emailBody), 'email', $application);
        }
    }

    private function safely(callable $send, string $channel, AdmissionApplication $application): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::error("[admissions] {$channel} notification failed", [
                'application' => $application->reference,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        Mail::raw($body, fn ($message) => $message->to($to)->subject($subject));
    }

    /**
     * ===================== INTEGRATION POINT =====================
     * Send through your active SMS gateway (the one configured under Settings → SMS gateways).
     * Until this is wired, SMS is only logged. Applicants can still get their code: it is shown
     * on screen when the application starts, and emailed if they gave an email address.
     */
    private function sendSms(string $phone, string $message): void
    {
        Log::info('[admissions] SMS not sent — gateway not wired yet', ['to' => $phone, 'message' => $message]);
    }
}

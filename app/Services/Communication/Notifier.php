<?php

// app/Services/Communication/Notifier.php
namespace App\Services\Communication;

use App\Models\AdmissionApplication;
use App\Models\CommunicationTemplate;

class Notifier
{
    public function __construct(protected CommunicationService $communications) {}

    /** Matches your existing call sites exactly — no controller changes needed. */
    public function sendCode(AdmissionApplication $application, string $code, ?string $onlyChannel = null): void
    {
        $this->dispatch(
            application: $application,
            triggerKey: 'admission.code_issued',
            placeholders: [
                'contact_name' => $application->contact_name,
                'reference'    => $application->reference,
                'code'         => $code,
            ],
            defaultSms: "Your application code is {$code}. Use it to continue or check your application status.",
            defaultEmailSubject: "Your application code — {$application->reference}",
            defaultEmailBody: "Hello {$application->contact_name},\n\nYour continuation code is {$code}.\n\nUse it any time to finish your application or check its status.",
            onlyChannel: $onlyChannel,
        );
    }

    public function notify(AdmissionApplication $application, string $subject, string $message, string $triggerKey = 'admission.status_update'): void
    {
        $this->dispatch(
            application: $application,
            triggerKey: $triggerKey,
            placeholders: [
                'contact_name' => $application->contact_name,
                'reference'    => $application->reference,
                'status'       => $application->statusLabel(),
            ],
            defaultSms: $message,
            defaultEmailSubject: $subject,
            defaultEmailBody: $message,
        );
    }

    protected function dispatch(
        AdmissionApplication $application,
        string $triggerKey,
        array $placeholders,
        string $defaultSms,
        string $defaultEmailSubject,
        string $defaultEmailBody,
        ?string $onlyChannel = null,
    ): void {
        $template = CommunicationTemplate::forTrigger($triggerKey);
        $wants = fn (string $channel) => $onlyChannel === null || $onlyChannel === $channel;

        if ($wants('sms') && $application->contact_phone) {
            $useTemplate = $template && $template->hasChannel('sms') && filled($template->sms_body);

            $this->communications->create([
                'communication_template_id' => $template?->id,
                'channel' => 'sms',
                'body' => $useTemplate ? $template->render('sms_body', $placeholders) : $defaultSms,
                'audience_type' => 'manual',
                'audience_params' => ['manual_recipients' => [$application->contact_phone]],
            ]);
        }

        if ($wants('email') && $application->contact_email) {
            $useTemplate = $template && $template->hasChannel('email') && filled($template->email_body);

            $this->communications->create([
                'communication_template_id' => $template?->id,
                'channel' => 'email',
                'subject' => $useTemplate ? $template->render('subject', $placeholders) : $defaultEmailSubject,
                'body' => $useTemplate ? $template->render('email_body', $placeholders) : $defaultEmailBody,
                'audience_type' => 'manual',
                'audience_params' => ['manual_recipients' => [$application->contact_email]],
            ]);
        }

        // WhatsApp has no hardcoded default copy — not every school configures it,
        // so it only fires when a template explicitly opts a trigger into it.
        if ($wants('whatsapp') && $template?->hasChannel('whatsapp') && filled($template->whatsapp_body) && $application->contact_phone) {
            $this->communications->create([
                'communication_template_id' => $template->id,
                'channel' => 'whatsapp',
                'body' => $template->render('whatsapp_body', $placeholders),
                'audience_type' => 'manual',
                'audience_params' => ['manual_recipients' => [$application->contact_phone]],
            ]);
        }
    }
}

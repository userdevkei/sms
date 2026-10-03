<p>This is a test email sent from the <strong>{{ $gatewayName }}</strong> gateway configured in {{ setting('school_name') }}.</p>

@if ($customMessage)
    <p>{{ $customMessage }}</p>
@endif

<p style="color:#64748b;font-size:.85rem;">Sent {{ now()->format('d M Y, H:i') }} to confirm this SMTP configuration works.</p>

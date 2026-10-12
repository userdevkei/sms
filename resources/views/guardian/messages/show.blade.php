@extends('guardian.layout')
@section('title', 'Message')

@section('content')
    @php
        $icons  = ['email' => 'bi-envelope', 'sms' => 'bi-chat-dots', 'whatsapp' => 'bi-whatsapp'];
        $labels = ['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'];
    @endphp

    <a href="{{ $backUrl }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Back to messages</a>

    <div class="g-card mt-3">
        <div class="g-card-head flex-wrap">
            <i class="bi {{ $icons[$content['channel']] ?? 'bi-chat' }}"></i>
            <span>{{ $content['subject'] ?: (($labels[$content['channel']] ?? 'Message') . ' message') }}</span>
            <span class="ms-auto small text-muted fw-normal">{{ $content['sent_at']->format('d M Y, H:i') }}</span>
        </div>

        <div class="g-card-body">
            <div class="mb-3">
                <span class="badge bg-light text-secondary border">{{ $labels[$content['channel']] ?? $content['channel'] }}</span>
                @if($content['child'])
                    <span class="badge bg-primary-subtle text-primary">About {{ $content['child']->full_name }}</span>
                @else
                    <span class="badge bg-success-subtle text-success">For you</span>
                @endif
            </div>

            @if($doc)
                {{-- Sandboxed: the message can display but can never run scripts. --}}
                <iframe id="msgFrame"
                        title="Message"
                        sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                        srcdoc="{{ $doc }}"
                        style="width:100%; border:0; min-height:320px; background:#f3f3f3; border-radius:.5rem;"></iframe>
            @else
                {{-- Escaped in GuardianInbox::formatText(), so this is safe to print unescaped. --}}
                <div class="p-3 rounded" style="background:#f6f8fb;">{!! $content['text_html'] !!}</div>
            @endif

            @if($content['has_amount'])
                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle"></i>
                    Amounts in fee reminders were correct on the day the message was sent. Open <strong>Statement</strong> for the current balance.
                </p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const frame = document.getElementById('msgFrame');
        if (frame) {
            const fit = () => { try { frame.style.height = (frame.contentDocument.documentElement.scrollHeight + 8) + 'px'; } catch (e) {} };
            frame.addEventListener('load', fit);
            window.addEventListener('resize', fit);
        }
    </script>
@endpush

{{-- resources/views/emails/communication.blade.php --}}
<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0;">
    <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:6px;overflow:hidden;">
                <tr>
                    <td style="background:{{ $brandColor }};padding:20px;text-align:center;">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" style="max-height:50px;">
                        @else
                            <span style="color:#fff;font-size:18px;font-weight:bold;">{{ $schoolName }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;color:#333;font-size:14px;line-height:1.6;">
                        {!! $bodyHtml !!}
                    </td>
                </tr>
                <tr>
                    <td style="background:#f0f0f0;padding:16px 24px;text-align:center;color:#888;font-size:12px;">
                        {{ $schoolName }} &middot; sent via automated communication system
                    </td>
                </tr>
            </table>
        </td></tr>
</table>
</body>
</html>

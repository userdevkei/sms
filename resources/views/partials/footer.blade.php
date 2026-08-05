<footer class="app-footer">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-1">
        <span>&copy; {{ now()->year }} {{ $appSettings->get('school_name', config('app.name')) }}. All rights reserved.</span>
        @if($appSettings->get('motto'))
            <span class="app-footer-motto">{{ $appSettings->get('motto') }}</span>
        @endif
    </div>
</footer>

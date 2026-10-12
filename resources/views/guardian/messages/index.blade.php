@extends('guardian.layout')
@section('title', 'Messages')

@push('styles')
    <style>.g-msg:hover { background: #f6f8fb; }</style>
@endpush

@section('content')
    @php
        $icons  = ['email' => 'bi-envelope', 'sms' => 'bi-chat-dots', 'whatsapp' => 'bi-whatsapp'];
        $labels = ['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'];
        $tabs   = ['all' => 'All', 'me' => 'For me', 'students' => 'About my students'];
        $showChildFilter = ! $child && $scope !== 'me' && $children->count() > 1;
    @endphp

    <div class="mb-3">
        <h1 class="g-h1">Messages</h1>
        <p class="g-sub">
            {{ $child ? "Messages the school has sent about {$child->full_name}." : 'Messages the school has sent you and about your students.' }}
        </p>
    </div>

    @unless($child)
        <ul class="nav nav-pills mb-3 gap-1">
            @foreach($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link py-1 px-3 {{ $scope === $key ? 'active' : '' }}"
                       href="{{ request()->fullUrlWithQuery(['scope' => $key === 'all' ? null : $key, 'child' => null, 'page' => null]) }}">
                        {{ $label }}
                        <span class="badge ms-1 {{ $scope === $key ? 'bg-light text-primary' : 'bg-secondary-subtle text-secondary' }}">{{ $counts[$key] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endunless

    <form method="GET" action="{{ url()->current() }}" class="g-card g-card-body mb-3">
        @if(! $child && $scope !== 'all')<input type="hidden" name="scope" value="{{ $scope }}">@endif
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Search subject</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="e.g. Fee balance">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Channel</label>
                <select name="channel" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($labels as $k => $l)
                        <option value="{{ $k }}" @selected($filters['channel'] === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @if($showChildFilter)
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Student</label>
                    <select name="child" class="form-select form-select-sm">
                        <option value="">All students</option>
                        @foreach($children as $c)
                            <option value="{{ $c->id }}" @selected($filters['child'] === $c->id)>{{ $c->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ $child ? url()->current() : request()->fullUrlWithQuery(['q' => null, 'channel' => null, 'child' => null, 'page' => null]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </form>

    <div class="g-card">
        @forelse($items as $m)
            <a href="{{ $m['url'] }}" class="g-msg d-flex gap-3 p-3 text-decoration-none text-reset border-bottom">
                <span class="g-avatar flex-none" style="width:40px;height:40px;"><i class="bi {{ $icons[$m['channel']] ?? 'bi-chat' }}"></i></span>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex justify-content-between gap-2">
                        <div class="fw-semibold text-truncate">{{ $m['subject'] ?: (($labels[$m['channel']] ?? 'Message') . ' message') }}</div>
                        <small class="text-muted text-nowrap">{{ $m['sent_at']->format('d M Y, H:i') }}</small>
                    </div>
                    <div class="text-muted small text-truncate">{{ $m['snippet'] }}</div>
                    <div class="mt-1">
                        <span class="badge bg-light text-secondary border">{{ $labels[$m['channel']] ?? $m['channel'] }}</span>
                        @if($m['kind'] === 'me')
                            <span class="badge bg-success-subtle text-success">For you</span>
                        @elseif(! $child && $m['child'])
                            <span class="badge bg-primary-subtle text-primary">{{ $m['child'] }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No messages yet.
            </div>
        @endforelse

        @if($paginator->hasPages())
            <div class="g-card-body">{{ $paginator->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection

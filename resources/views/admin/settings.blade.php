@extends('layouts.app')
@section('title', 'School Settings')

@section('content')
    <div class="d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-gear-fill fs-4 text-primary"></i>
        <h1 class="h4 mb-0">School Settings</h1>
    </div>

    <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#branding-pane" type="button">
                <i class="bi bi-palette me-1"></i> Branding
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sms-pane" type="button">
                <i class="bi bi-chat-dots me-1"></i> SMS Gateway
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#whatsapp-pane" type="button">
                <i class="bi bi-whatsapp me-1"></i> WhatsApp Gateway
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#payment-pane" type="button">
                <i class="bi bi-credit-card me-1"></i> Payment Gateway
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#email-pane" type="button">
                <i class="bi bi-envelope me-1"></i> Email Gateway
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- ============ BRANDING ============ --}}
        <div class="tab-pane fade show active" id="branding-pane" role="tabpanel">
            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="card border-0 shadow-sm settings-card">
                @csrf @method('PUT')
                <div class="card-body p-4 p-md-5">

                    <div class="settings-section-header">
                        <i class="bi bi-palette"></i>
                        <h6>Branding</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">School Name</label>
                            <input type="text" name="school_name" class="form-control"
                                   value="{{ old('school_name', $settings->get('school_name')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="tagline" class="form-control"
                                   value="{{ old('tagline', $settings->get('tagline')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motto</label>
                            <input type="text" name="motto" class="form-control"
                                   value="{{ old('motto', $settings->get('motto')) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Logo</label>
                            <div class="branding-upload">
                                <div class="branding-preview" id="logo-preview-wrap">
                                    @if($settings->get('logo_path'))
                                        <img id="logo-preview" src="{{ route('file', ['path' => $settings->get('logo_path')]) }}" alt="Current logo">
                                    @else
                                        <img id="logo-preview" src="" alt="Logo preview" class="d-none">
                                        <i class="bi bi-image text-muted" id="logo-placeholder-icon"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="logo" id="logo-input" class="form-control" accept="image/*">
                                    <small class="text-muted">PNG or SVG, square works best. Max 2MB.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Favicon</label>
                            <div class="branding-upload">
                                <div class="branding-preview branding-preview-sm" id="favicon-preview-wrap">
                                    @if($settings->get('favicon_path'))
                                        <img id="favicon-preview" src="{{ route('file', ['path' =>$settings->get('favicon_path')]) }}" alt="Current favicon">
                                    @else
                                        <img id="favicon-preview" src="" alt="Favicon preview" class="d-none">
                                        <i class="bi bi-image text-muted" id="favicon-placeholder-icon"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="favicon" id="favicon-input" class="form-control" accept="image/*">
                                    <small class="text-muted">ICO, PNG, or SVG. Max 512KB.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section-header">
                        <i class="bi bi-brush"></i>
                        <h6>Theme Colors</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Primary Color</label>
                            <input type="color" name="primary_color" class="form-control form-control-color w-100"
                                   value="{{ old('primary_color', $settings->get('primary_color', '#0B3D62')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secondary Color</label>
                            <input type="color" name="secondary_color" class="form-control form-control-color w-100"
                                   value="{{ old('secondary_color', $settings->get('secondary_color', '#0E8388')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sidebar Color</label>
                            <input type="color" name="sidebar_color" class="form-control form-control-color w-100"
                                   value="{{ old('sidebar_color', $settings->get('sidebar_color', '#0B3D62')) }}">
                        </div>
                    </div>

                    <div class="settings-section-header">
                        <i class="bi bi-envelope"></i>
                        <h6>Contact &amp; General</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="{{ old('address', $settings->get('address')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone', $settings->get('phone')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" class="form-control"
                                   value="{{ old('currency', $settings->get('currency', 'KES')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $settings->get('email')) }}">
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>

        {{-- ============ SMS GATEWAY ============ --}}
        <div class="tab-pane fade" id="sms-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">Only one SMS gateway is active at a time — it's used for every outgoing SMS.</p>
                <button type="button" class="btn btn-sm btn-primary" onclick="openSmsModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add SMS Gateway
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm table-striped fs-sm w-100">
                            <thead><tr><th>Name</th><th>Provider</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            @forelse($smsGateways as $gw)
                                <tr>
                                    <td class="fw-semibold">{{ $gw->name }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $gw->provider) }}</td>
                                    <td>
                                        @if($gw->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-info me-1 btn-test-sms" data-id="{{ $gw->id }}" data-name="{{ $gw->name }}" title="Send test SMS">
                                            <i class="bi bi-send-check"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="activateGateway('sms', '{{ $gw->id }}')">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        @endunless
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-sms"
                                                data-config="{{ json_encode(array_merge($gw->only(['id', 'provider', 'name']), $gw->config())) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGateway('sms', '{{ $gw->id }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No SMS gateways configured.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ WHATSAPP GATEWAY ============ --}}
        <div class="tab-pane fade" id="whatsapp-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">Only one WhatsApp gateway is active at a time — it's used for every outgoing WhatsApp message.</p>
                <button type="button" class="btn btn-sm btn-primary" onclick="openWhatsappModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add WhatsApp Gateway
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm table-striped fs-sm w-100">
                            <thead><tr><th>Name</th><th>Provider</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            @forelse($whatsappGateways as $gw)
                                <tr>
                                    <td class="fw-semibold">{{ $gw->name }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $gw->provider) }}</td>
                                    <td>
                                        @if($gw->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-info me-1 btn-test-whatsapp" data-id="{{ $gw->id }}" data-name="{{ $gw->name }}" title="Send test WhatsApp message">
                                            <i class="bi bi-send-check"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="activateGateway('whatsapp', '{{ $gw->id }}')">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        @endunless
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-whatsapp"
                                                data-config="{{ json_encode(array_merge($gw->only(['id', 'provider', 'name']), $gw->config())) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGateway('whatsapp', '{{ $gw->id }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No WhatsApp gateways configured.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ PAYMENT GATEWAY ============ --}}
        <div class="tab-pane fade" id="payment-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">Only one payment gateway is active at a time — it's used for all M-Pesa/bank transactions.</p>
                <button type="button" class="btn btn-sm btn-primary" onclick="openPaymentModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add Payment Gateway
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="paymentGatewaysTable" class="table table-hover align-middle w-100  fs-sm table-striped">
                            <thead><tr><th>Name</th><th>Provider</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            @forelse($paymentGateways as $gw)
                                <tr>
                                    <td class="fw-semibold">{{ $gw->name }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $gw->provider) }}</td>
                                    <td>
                                        @if($gw->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="activateGateway('payment', '{{ $gw->id }}')">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        @endunless
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-payment"
                                                data-config="{{ json_encode(array_merge($gw->only(['id', 'provider', 'name']), $gw->config())) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGateway('payment', '{{ $gw->id }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No payment gateways configured.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ EMAIL GATEWAY ============ --}}
        <div class="tab-pane fade" id="email-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">Only one email (SMTP) gateway is active at a time.</p>
                <button type="button" class="btn btn-sm btn-primary" onclick="openEmailModal()">
                    <i class="bi bi-plus-lg me-1"></i> Add Email Gateway
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm table-striped fs-sm w-100">
                            <thead><tr><th>Name</th><th>Host</th><th>From Address</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            @forelse($emailGateways as $gw)
                                @php $cfg = $gw->config(); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $gw->name }}</td>
                                    <td>{{ $cfg['host'] ?? '—' }}</td>
                                    <td>{{ $cfg['from_address'] ?? '—' }}</td>
                                    <td>
                                        @if($gw->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-info me-1 btn-test-email" data-id="{{ $gw->id }}" data-name="{{ $gw->name }}" title="Send test email">
                                            <i class="bi bi-send-check"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-success me-1" onclick="activateGateway('email', '{{ $gw->id }}')">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        @endunless
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-email"
                                                data-config="{{ json_encode(array_merge($gw->only(['id', 'name']), $cfg)) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @unless($gw->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteGateway('email', '{{ $gw->id }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No email gateways configured.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ============ SMS GATEWAY MODAL ============ --}}
    <div class="modal fade" id="smsModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="smsForm" action="{{ route('settings.sms-gateways.store') }}">
                @csrf
                <input type="hidden" name="_method" id="smsMethodField" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="smsModalTitle">Add SMS Gateway</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="sms_name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Provider <span class="text-danger">*</span></label>
                            <select name="provider" id="sms_provider" class="form-select" required onchange="toggleSmsProviderFields()">
                                <option value="africas_talking">Africa's Talking</option>
                                <option value="twilio">Twilio</option>
                                <option value="custom">Custom (API Endpoint)</option>
                            </select>
                        </div>

                        <div id="sms_africas_talking_fields">
                            <div class="mb-2">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="sms_username" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" id="sms_api_key_at" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="sms_api_key_at_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Sender ID</label>
                                <input type="text" name="sender_id" id="sms_sender_id" class="form-control">
                            </div>
                        </div>

                        <div id="sms_twilio_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Account SID <span class="text-danger">*</span></label>
                                <input type="text" name="account_sid" id="sms_twilio_account_sid" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Auth Token <span class="text-danger">*</span></label>
                                <input type="password" name="auth_token" id="sms_twilio_auth_token" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="sms_twilio_auth_token_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">From (Twilio SMS-capable number) <span class="text-danger">*</span></label>
                                <input type="text" name="from" id="sms_twilio_from" class="form-control" placeholder="+15005550006">
                                <small class="text-muted">For sandbox testing, use Twilio's test/trial number here.</small>
                            </div>
                        </div>

                        <div id="sms_custom_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Endpoint URL <span class="text-danger">*</span></label>
                                <input type="url" name="endpoint_url" id="sms_endpoint_url" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" id="sms_api_key_custom" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="sms_api_key_custom_hint"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ WHATSAPP GATEWAY MODAL ============ --}}
    <div class="modal fade" id="whatsappModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="whatsappForm" action="{{ route('settings.whatsapp-gateways.store') }}">
                @csrf
                <input type="hidden" name="_method" id="whatsappMethodField" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="whatsappModalTitle">Add WhatsApp Gateway</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="whatsapp_name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Provider <span class="text-danger">*</span></label>
                            <select name="provider" id="whatsapp_provider" class="form-select" required onchange="toggleWhatsappProviderFields()">
                                <option value="whatsapp_cloud">WhatsApp Cloud API (Meta)</option>
                                <option value="twilio">Twilio (sandbox or WhatsApp-enabled number)</option>
                                <option value="custom">Custom (API Endpoint)</option>
                            </select>
                        </div>

                        {{-- ---- WHATSAPP CLOUD API (META) ---- --}}
                        <div id="whatsapp_whatsapp_cloud_fields">
                            <div class="mb-2">
                                <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number_id" id="whatsapp_cloud_phone_number_id" class="form-control">
                                <small class="text-muted">From Meta Business Suite → WhatsApp → API Setup.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">WhatsApp Business Account ID</label>
                                <input type="text" name="business_account_id" id="whatsapp_cloud_business_account_id" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Access Token <span class="text-danger">*</span></label>
                                <input type="password" name="access_token" id="whatsapp_cloud_access_token" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="whatsapp_cloud_access_token_hint">A permanent token from a System User, not the 24-hour temporary token.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Webhook Verify Token</label>
                                <input type="text" name="verify_token" id="whatsapp_cloud_verify_token" class="form-control">
                                <small class="text-muted">You choose this value yourself and enter the same one in Meta's webhook setup.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Webhook Callback URL (register this with Meta)</label>
                                <input type="url" class="form-control" value="{{ route('webhooks.whatsapp.cloud') }}" readonly>
                            </div>
                        </div>

                        {{-- ---- TWILIO ---- --}}
                        <div id="whatsapp_twilio_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Account SID <span class="text-danger">*</span></label>
                                <input type="text" name="account_sid" id="whatsapp_twilio_account_sid" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Auth Token <span class="text-danger">*</span></label>
                                <input type="password" name="auth_token" id="whatsapp_twilio_auth_token" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="whatsapp_twilio_auth_token_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">From (WhatsApp-enabled number) <span class="text-danger">*</span></label>
                                <input type="text" name="from" id="whatsapp_twilio_from" class="form-control" placeholder="+14155238886">
                                <small class="text-muted">Twilio's sandbox number works here for testing — sent with the whatsapp: prefix automatically, don't include it yourself.</small>
                            </div>
                        </div>

                        {{-- ---- CUSTOM ---- --}}
                        <div id="whatsapp_custom_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Endpoint URL <span class="text-danger">*</span></label>
                                <input type="url" name="endpoint_url" id="whatsapp_custom_endpoint_url" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" id="whatsapp_custom_api_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="whatsapp_custom_api_key_hint"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ PAYMENT GATEWAY MODAL ============ --}}
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="paymentForm" action="{{ route('settings.payment-gateways.store') }}">
                @csrf
                <input type="hidden" name="_method" id="paymentMethodField" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalTitle">Add Payment Gateway</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger" id="paymentFormErrors">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="payment_name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Provider <span class="text-danger">*</span></label>
                            <select name="provider" id="payment_provider" class="form-select" required onchange="togglePaymentProviderFields()">
                                <option value="mpesa">M-Pesa (Daraja)</option>
                                <option value="equity">Equity Bank (Jenga IPN)</option>
                                <option value="kcb">KCB (Buni IPN)</option>
                                <option value="coop">Co-operative Bank</option>
                            </select>
                        </div>

                        {{-- ---- M-PESA ---- --}}
                        <div id="payment_mpesa_fields">
                            <div class="mb-2">
                                <label class="form-label">Environment <span class="text-danger">*</span></label>
                                <select name="environment" id="payment_mpesa_environment" class="form-select">
                                    <option value="sandbox" @selected(old('environment') === 'sandbox')>Sandbox</option>
                                    <option value="live" @selected(old('environment') === 'live')>Live</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Consumer Key</label>
                                <input type="password" name="consumer_key" id="payment_mpesa_consumer_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_mpesa_consumer_key_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Consumer Secret</label>
                                <input type="password" name="consumer_secret" id="payment_mpesa_consumer_secret" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_mpesa_consumer_secret_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Shortcode <span class="text-danger">*</span></label>
                                <input type="text" name="shortcode" id="payment_mpesa_shortcode" class="form-control" value="{{ old('shortcode') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Passkey</label>
                                <input type="password" name="passkey" id="payment_mpesa_passkey" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_mpesa_passkey_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Callback URL</label>
                                <input type="url" class="form-control" value="{{ route('mpesa.callback') }}" readonly>
                            </div>
                        </div>

                        {{-- ---- EQUITY (Jenga IPN) ---- --}}
                        <div id="payment_equity_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Environment <span class="text-danger">*</span></label>
                                <select name="environment" id="payment_equity_environment" class="form-select">
                                    <option value="sandbox" @selected(old('environment') === 'sandbox')>Sandbox</option>
                                    <option value="live" @selected(old('environment') === 'live')>Live</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Account / Bill Number <span class="text-danger">*</span></label>
                                <input type="text" name="account_number" id="payment_equity_account_number" class="form-control" value="{{ old('account_number') }}">
                                <small class="text-muted">Tell parents/tellers to use the student's admission number as the Bill Number.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">IPN Basic Auth Username <span class="text-danger">*</span></label>
                                <input type="text" name="ipn_username" id="payment_equity_ipn_username" class="form-control" autocomplete="off" value="{{ old('ipn_username') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">IPN Basic Auth Password <span class="text-danger">*</span></label>
                                <input type="password" name="ipn_password" id="payment_equity_ipn_password" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_equity_ipn_password_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Callback URL (register this with Equity)</label>
                                <input type="url" class="form-control" value="{{ route('webhooks.banks.equity') }}" readonly>
                            </div>
                        </div>

                        {{-- ---- KCB (Buni IPN) ---- --}}
                        <div id="payment_kcb_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Environment <span class="text-danger">*</span></label>
                                <select name="environment" id="payment_kcb_environment" class="form-select">
                                    <option value="sandbox" @selected(old('environment') === 'sandbox')>Sandbox</option>
                                    <option value="live" @selected(old('environment') === 'live')>Live</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Till / Organization Short Code <span class="text-danger">*</span></label>
                                <input type="text" name="account_number" id="payment_kcb_account_number" class="form-control" value="{{ old('account_number') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">KCB Public Key (PEM) <span class="text-danger">*</span></label>
                                <textarea name="kcb_public_key" id="payment_kcb_public_key" class="form-control" rows="4" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----">{{ old('kcb_public_key') }}</textarea>
                                <small class="text-muted">Used to verify the SHA256withRSA signature KCB attaches to every IPN. Issued to you during IPN onboarding — this is a public key, not a secret, so it's fine to display in full.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Consumer Key</label>
                                <input type="password" name="consumer_key" id="payment_kcb_consumer_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted">Only needed for outbound Buni calls (e.g. STK Push) — not required for IPN alone.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Consumer Secret</label>
                                <input type="password" name="consumer_secret" id="payment_kcb_consumer_secret" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_kcb_consumer_secret_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Callback URL (register this with KCB)</label>
                                <input type="url" class="form-control" value="{{ route('webhooks.banks.kcb') }}" readonly>
                            </div>
                        </div>

                        {{-- ---- CO-OP BANK ---- --}}
                        <div id="payment_coop_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Environment <span class="text-danger">*</span></label>
                                <select name="environment" id="payment_coop_environment" class="form-select">
                                    <option value="sandbox" @selected(old('environment') === 'sandbox')>Sandbox</option>
                                    <option value="live" @selected(old('environment') === 'live')>Live</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Account Number <span class="text-danger">*</span></label>
                                <input type="text" name="account_number" id="payment_coop_account_number" class="form-control" value="{{ old('account_number') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" id="payment_coop_api_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_coop_api_key_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">IPN Auth Value</label>
                                <input type="password" name="ipn_key" id="payment_coop_ipn_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted">Unconfirmed field — update once Co-op's actual IPN auth spec is confirmed.</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Callback URL (register this with Co-op)</label>
                                <input type="url" class="form-control" value="{{ route('webhooks.banks.coop') }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ EMAIL GATEWAY MODAL ============ --}}
    <div class="modal fade" id="emailModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="emailForm" action="{{ route('settings.email-gateways.store') }}">
                @csrf
                <input type="hidden" name="_method" id="emailMethodField" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="emailModalTitle">Add Email Gateway</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="email_name" class="form-control" required>
                        </div>
                        <div class="row g-2">
                            <div class="col-8">
                                <label class="form-label">Host <span class="text-danger">*</span></label>
                                <input type="text" name="host" id="email_host" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Port <span class="text-danger">*</span></label>
                                <input type="number" name="port" id="email_port" class="form-control">
                            </div>
                        </div>
                        <div class="mb-2 mt-2">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="email_username" class="form-control" autocomplete="username">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="email_password" class="form-control" autocomplete="new-password">
                            <small class="text-muted" id="email_password_hint"></small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Encryption <span class="text-danger">*</span></label>
                            <select name="encryption" id="email_encryption" class="form-select">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">From Address <span class="text-danger">*</span></label>
                            <input type="email" name="from_address" id="email_from_address" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">From Name <span class="text-danger">*</span></label>
                            <input type="text" name="from_name" id="email_from_name" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ SHARED TEST-GATEWAY MODAL (SMS / WhatsApp / Email) ============ --}}
    <div class="modal fade" id="testGatewayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testGatewayTitle">Send Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{--
                        FIX (was crashing, twice): #testGatewayAlert used to be
                        server-rendered here — first as a div holding only "&nbsp;",
                        then as a div holding an empty <span>. Both got stripped from
                        the response by whatever is minifying the HTML: it appears to
                        remove empty elements recursively (empty span removed first,
                        which then leaves the div empty too, so it gets removed on a
                        second pass). Any markup-only trick is fragile against that.

                        Real fix: stop depending on server-rendered markup for this
                        element. It's now created in JS at runtime by
                        getTestGatewayAlert() (see script below), which only ever
                        touches the live DOM — nothing server-side can strip it.
                    --}}
                    <p class="text-muted small mb-3" id="testGatewaySubtitle"></p>

                    <div class="mb-2">
                        <label class="form-label" id="testGatewayDestinationLabel">Destination</label>
                        <input type="text" id="testGatewayDestination" class="form-control" placeholder="">
                    </div>
                    <div class="mb-0" id="testGatewayMessageWrap">
                        <label class="form-label">Message</label>
                        <textarea id="testGatewayMessage" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-primary" id="testGatewaySubmit" onclick="submitTestGateway()">
                        <span id="testGatewaySubmitLabel">Send Test</span>
                        <span class="spinner-border spinner-border-sm d-none" id="testGatewaySpinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if ($errors->any() && old('provider'))
            document.getElementById('payment_provider').value = @json(old('provider'));
            togglePaymentProviderFields();
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
            @endif

            function bindPreview(inputId, imgId, iconId) {
                var input = document.getElementById(inputId);
                var img = document.getElementById(imgId);
                var icon = document.getElementById(iconId);
                if (!input) return;
                input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    if (!file) return;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        img.src = e.target.result;
                        img.classList.remove('d-none');
                        if (icon) icon.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                });
            }
            bindPreview('logo-input', 'logo-preview', 'logo-placeholder-icon');
            bindPreview('favicon-input', 'favicon-preview', 'favicon-placeholder-icon');
        });

        ['paymentGatewaysTable'].forEach(id => {
            if (document.getElementById(id)) {
                $('#' + id).DataTable({ order: [[0, 'asc']], pageLength: 10, columnDefs: [{ targets: -1, orderable: false }] });
            }
        });

        const routes = {
            smsStore: @json(route('settings.sms-gateways.store')),
            smsUpdate: id => @json(route('settings.sms-gateways.update', ['smsGateway' => '__ID__'])).replace('__ID__', id),
            whatsappStore: @json(route('settings.whatsapp-gateways.store')),
            whatsappUpdate: id => @json(route('settings.whatsapp-gateways.update', ['whatsappGateway' => '__ID__'])).replace('__ID__', id),
            whatsappActivate: id => @json(route('settings.whatsapp-gateways.activate', ['whatsappGateway' => '__ID__'])).replace('__ID__', id),
            whatsappDestroy: id => @json(route('settings.whatsapp-gateways.destroy', ['whatsappGateway' => '__ID__'])).replace('__ID__', id),
            paymentStore: @json(route('settings.payment-gateways.store')),
            paymentUpdate: id => @json(route('settings.payment-gateways.update', ['paymentGateway' => '__ID__'])).replace('__ID__', id),
            emailStore: @json(route('settings.email-gateways.store')),
            emailUpdate: id => @json(route('settings.email-gateways.update', ['emailGateway' => '__ID__'])).replace('__ID__', id),

            // TODO (backend): sms/payment activate+destroy still guessed string paths,
            // not real route() calls — paste `php artisan route:list` filtered to
            // "sms-gateways" and "payment-gateways" and these will get fixed properly.
            activate: (type, id) => type === 'whatsapp' ? routes.whatsappActivate(id) : `/settings/${type}-gateways/${id}/activate`,
            destroy: (type, id) => type === 'whatsapp' ? routes.whatsappDestroy(id) : `/settings/${type}-gateways/${id}`,

            // TODO (backend): SMS test is still a guessed string path, no
            // controller/route behind it yet — do the same thing for it that was
            // just done for WhatsApp (mirroring settings.email-gateways.test).
            testGateway: (channel, id) => {
                const map = {
                    sms:      '/settings/sms-gateways/__ID__/test',  // NOT YET WIRED
                    whatsapp: @json(route('settings.whatsapp-gateways.test', ['whatsappGateway' => '__ID__'])),
                    email:    @json(route('settings.email-gateways.test', ['emailGateway' => '__ID__'])),
                };
                return map[channel].replace('__ID__', id);
            },
        };

        const SMS_PROVIDER_FIELDSETS = ['africas_talking', 'twilio', 'custom'];
        const WHATSAPP_PROVIDER_FIELDSETS = ['whatsapp_cloud', 'twilio', 'custom'];
        const PAYMENT_PROVIDER_FIELDSETS = ['mpesa', 'equity', 'kcb', 'coop'];

        function toggleSmsProviderFields() {
            const provider = document.getElementById('sms_provider').value;
            SMS_PROVIDER_FIELDSETS.forEach(p => {
                const fieldset = document.getElementById('sms_' + p + '_fields');
                const isActive = p === provider;
                fieldset.classList.toggle('d-none', !isActive);
                fieldset.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = !isActive; });
            });
        }

        function toggleWhatsappProviderFields() {
            const provider = document.getElementById('whatsapp_provider').value;
            WHATSAPP_PROVIDER_FIELDSETS.forEach(p => {
                const fieldset = document.getElementById('whatsapp_' + p + '_fields');
                const isActive = p === provider;
                fieldset.classList.toggle('d-none', !isActive);
                fieldset.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = !isActive; });
            });
        }

        function togglePaymentProviderFields() {
            const provider = document.getElementById('payment_provider').value;
            PAYMENT_PROVIDER_FIELDSETS.forEach(p => {
                const fieldset = document.getElementById('payment_' + p + '_fields');
                const isActive = p === provider;
                fieldset.classList.toggle('d-none', !isActive);
                // Disabled inputs are excluded from form submission entirely —
                // required since equity/kcb/coop all reuse names like
                // "account_number" and "environment"; without this, every
                // hidden fieldset's value gets submitted too, and the last
                // one in DOM order silently wins over whichever you filled in.
                fieldset.querySelectorAll('input, select, textarea').forEach(el => {
                    el.disabled = !isActive;
                });
            });
        }

        function openSmsModal(data) {
            const form = document.getElementById('smsForm');
            const isEdit = !!data;
            const provider = data?.provider ?? 'africas_talking';
            const hint = isEdit ? 'Leave blank to keep the current value.' : '';

            document.getElementById('smsModalTitle').textContent = isEdit ? 'Edit SMS Gateway' : 'Add SMS Gateway';
            form.action = isEdit ? routes.smsUpdate(data.id) : routes.smsStore;
            document.getElementById('smsMethodField').value = isEdit ? 'PATCH' : '';

            document.getElementById('sms_name').value = data?.name ?? '';
            document.getElementById('sms_provider').value = provider;
            document.getElementById('sms_username').value = data?.username ?? '';
            document.getElementById('sms_sender_id').value = data?.sender_id ?? '';
            document.getElementById('sms_twilio_account_sid').value = data?.account_sid ?? '';
            document.getElementById('sms_twilio_from').value = data?.from ?? '';
            document.getElementById('sms_endpoint_url').value = data?.endpoint_url ?? '';

            ['sms_api_key_at', 'sms_twilio_auth_token', 'sms_api_key_custom'].forEach(id => { document.getElementById(id).value = ''; });
            ['sms_api_key_at_hint', 'sms_twilio_auth_token_hint', 'sms_api_key_custom_hint'].forEach(id => { document.getElementById(id).textContent = hint; });

            toggleSmsProviderFields();
            new bootstrap.Modal(document.getElementById('smsModal')).show();
        }

        function openWhatsappModal(data) {
            const form = document.getElementById('whatsappForm');
            const isEdit = !!data;
            const provider = data?.provider ?? 'whatsapp_cloud';
            const hint = isEdit ? 'Leave blank to keep the current value.' : '';

            document.getElementById('whatsappModalTitle').textContent = isEdit ? 'Edit WhatsApp Gateway' : 'Add WhatsApp Gateway';
            form.action = isEdit ? routes.whatsappUpdate(data.id) : routes.whatsappStore;
            document.getElementById('whatsappMethodField').value = isEdit ? 'PATCH' : '';

            document.getElementById('whatsapp_name').value = data?.name ?? '';
            document.getElementById('whatsapp_provider').value = provider;

            document.getElementById('whatsapp_cloud_phone_number_id').value = data?.phone_number_id ?? '';
            document.getElementById('whatsapp_cloud_business_account_id').value = data?.business_account_id ?? '';
            document.getElementById('whatsapp_cloud_verify_token').value = data?.verify_token ?? '';
            document.getElementById('whatsapp_twilio_account_sid').value = data?.account_sid ?? '';
            document.getElementById('whatsapp_twilio_from').value = data?.from ?? '';
            document.getElementById('whatsapp_custom_endpoint_url').value = data?.endpoint_url ?? '';

            ['whatsapp_cloud_access_token', 'whatsapp_twilio_auth_token', 'whatsapp_custom_api_key'].forEach(id => { document.getElementById(id).value = ''; });
            ['whatsapp_cloud_access_token_hint', 'whatsapp_twilio_auth_token_hint', 'whatsapp_custom_api_key_hint'].forEach(id => { document.getElementById(id).textContent = hint; });

            toggleWhatsappProviderFields();
            new bootstrap.Modal(document.getElementById('whatsappModal')).show();
        }

        function openPaymentModal(data) {
            const form = document.getElementById('paymentForm');
            const isEdit = !!data;
            const provider = data?.provider ?? 'mpesa';
            const hint = isEdit ? 'Leave blank to keep the current value.' : '';

            document.getElementById('paymentModalTitle').textContent = isEdit ? 'Edit Payment Gateway' : 'Add Payment Gateway';
            form.action = isEdit ? routes.paymentUpdate(data.id) : routes.paymentStore;
            document.getElementById('paymentMethodField').value = isEdit ? 'PATCH' : '';

            document.getElementById('payment_name').value = data?.name ?? '';
            document.getElementById('payment_provider').value = provider;

            [
                'payment_mpesa_consumer_key', 'payment_mpesa_consumer_secret', 'payment_mpesa_passkey',
                'payment_equity_ipn_password',
                'payment_kcb_consumer_key', 'payment_kcb_consumer_secret',
                'payment_coop_api_key', 'payment_coop_ipn_key',
            ].forEach(id => { document.getElementById(id).value = ''; });

            [
                'payment_mpesa_consumer_key_hint', 'payment_mpesa_consumer_secret_hint', 'payment_mpesa_passkey_hint',
                'payment_equity_ipn_password_hint',
                'payment_kcb_consumer_secret_hint',
                'payment_coop_api_key_hint',
            ].forEach(id => { document.getElementById(id).textContent = hint; });

            document.getElementById('payment_mpesa_environment').value = data?.environment ?? 'sandbox';
            document.getElementById('payment_mpesa_shortcode').value = data?.shortcode ?? '';

            document.getElementById('payment_equity_environment').value = data?.environment ?? 'sandbox';
            document.getElementById('payment_equity_account_number').value = data?.account_number ?? '';
            document.getElementById('payment_equity_ipn_username').value = data?.ipn_username ?? '';

            document.getElementById('payment_kcb_environment').value = data?.environment ?? 'sandbox';
            document.getElementById('payment_kcb_account_number').value = data?.account_number ?? '';
            document.getElementById('payment_kcb_public_key').value = data?.kcb_public_key ?? '';

            document.getElementById('payment_coop_environment').value = data?.environment ?? 'sandbox';
            document.getElementById('payment_coop_account_number').value = data?.account_number ?? '';

            togglePaymentProviderFields();
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        function openEmailModal(data) {
            const form = document.getElementById('emailForm');
            const isEdit = !!data;
            document.getElementById('emailModalTitle').textContent = isEdit ? 'Edit Email Gateway' : 'Add Email Gateway';
            form.action = isEdit ? routes.emailUpdate(data.id) : routes.emailStore;
            document.getElementById('emailMethodField').value = isEdit ? 'PATCH' : '';

            document.getElementById('email_name').value = data?.name ?? '';
            document.getElementById('email_host').value = data?.host ?? '';
            document.getElementById('email_port').value = data?.port ?? '';
            document.getElementById('email_username').value = data?.username ?? '';
            document.getElementById('email_password').value = '';
            document.getElementById('email_password_hint').textContent = isEdit ? 'Leave blank to keep the current password.' : '';
            document.getElementById('email_encryption').value = data?.encryption ?? 'tls';
            document.getElementById('email_from_address').value = data?.from_address ?? '';
            document.getElementById('email_from_name').value = data?.from_name ?? '';

            new bootstrap.Modal(document.getElementById('emailModal')).show();
        }

        function activateGateway(type, id) {
            if (! confirm('Make this the active ' + type + ' gateway? The current active one will be deactivated.')) return;
            fetch(routes.activate(type, id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
            }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
        }

        function deleteGateway(type, id) {
            if (! confirm('Delete this gateway configuration?')) return;
            fetch(routes.destroy(type, id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' }
            }).then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
        }

        // ---- Test-gateway modal (shared across sms / whatsapp / email) ----
        let testGatewayState = { channel: null, id: null };

        function getTestGatewayAlert() {
            let alertBox = document.getElementById('testGatewayAlert');
            if (alertBox) return alertBox;

            // Server-rendered markup for this element keeps getting stripped by
            // whatever minifies the HTML response (it removes empty elements,
            // recursively). Build it at runtime instead — JS runs after
            // minification, so nothing server-side can touch it here.
            alertBox = document.createElement('div');
            alertBox.id = 'testGatewayAlert';
            alertBox.className = 'alert d-none';
            alertBox.setAttribute('role', 'alert');

            const modalBody = document.querySelector('#testGatewayModal .modal-body');
            if (modalBody) {
                modalBody.prepend(alertBox);
            }
            // If modalBody is also missing, alertBox stays detached (won't be
            // visible on screen) but still exists as an object, so nothing
            // downstream throws trying to set its className/textContent.
            return alertBox;
        }

        const TEST_MODAL_CONFIG = {
            sms:      { title: 'Send Test SMS',              label: 'Phone number', placeholder: '0712 345 678', hasMessage: true },
            whatsapp: { title: 'Send Test WhatsApp Message',  label: 'Phone number', placeholder: '0712 345 678', hasMessage: true },
            email:    { title: 'Send Test Email',             label: 'Email address', placeholder: 'you@example.com', hasMessage: false },
        };

        function openTestModal(channel, id, name) {
            testGatewayState = { channel, id };
            const cfg = TEST_MODAL_CONFIG[channel];
            const alertBox = getTestGatewayAlert();

            document.getElementById('testGatewayTitle').textContent = cfg.title;
            document.getElementById('testGatewaySubtitle').textContent = 'Testing gateway: ' + name;
            document.getElementById('testGatewayDestinationLabel').textContent = cfg.label;
            document.getElementById('testGatewayDestination').placeholder = cfg.placeholder;
            document.getElementById('testGatewayDestination').value = '';
            document.getElementById('testGatewayMessageWrap').classList.toggle('d-none', !cfg.hasMessage);
            document.getElementById('testGatewayMessage').value = cfg.hasMessage ? ('This is a test message from ' + name + '.') : '';

            alertBox.className = 'alert d-none';
            alertBox.textContent = '';

            new bootstrap.Modal(document.getElementById('testGatewayModal')).show();
        }

        function submitTestGateway() {
            const { channel, id } = testGatewayState;
            const destinationEl = document.getElementById('testGatewayDestination');
            const messageEl = document.getElementById('testGatewayMessage');
            const alertBox = getTestGatewayAlert();
            const submitBtn = document.getElementById('testGatewaySubmit');
            const spinner = document.getElementById('testGatewaySpinner');
            const label = document.getElementById('testGatewaySubmitLabel');

            // Defensive guard: if any expected element is missing, fail with a
            // clear message instead of a cryptic "Cannot set properties of null".
            // (alertBox itself can no longer be null — getTestGatewayAlert() always
            // returns an element — but the rest are still worth checking.)
            if (!submitBtn || !spinner || !label || !destinationEl || !messageEl) {
                console.error('testGatewayModal: expected element missing from DOM', { submitBtn, spinner, label, destinationEl, messageEl });
                alert('Something went wrong with the test dialog — please reload the page and try again.');
                return;
            }

            const destination = destinationEl.value.trim();
            const message = messageEl.value.trim();

            if (!destination) {
                alertBox.className = 'alert alert-warning';
                alertBox.textContent = 'Please enter a destination.';
                return;
            }

            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            label.textContent = 'Sending…';
            alertBox.className = 'alert d-none';

            fetch(routes.testGateway(channel, id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ destination, message }),
            })
                .then(r => r.json())
                .then(res => {
                    alertBox.className = 'alert ' + (res.success ? 'alert-success' : 'alert-danger');
                    alertBox.textContent = res.message ?? (res.success ? 'Test sent.' : 'Test failed.');
                })
                .catch(() => {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = 'Request failed — check your connection and try again.';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                    label.textContent = 'Send Test';
                });
        }

        document.querySelectorAll('.btn-edit-sms').forEach(btn => {
            btn.addEventListener('click', function () {
                openSmsModal(JSON.parse(this.dataset.config));
            });
        });

        document.querySelectorAll('.btn-edit-whatsapp').forEach(btn => {
            btn.addEventListener('click', function () {
                openWhatsappModal(JSON.parse(this.dataset.config));
            });
        });

        document.querySelectorAll('.btn-edit-payment').forEach(btn => {
            btn.addEventListener('click', function () {
                openPaymentModal(JSON.parse(this.dataset.config));
            });
        });

        document.querySelectorAll('.btn-edit-email').forEach(btn => {
            btn.addEventListener('click', function () {
                openEmailModal(JSON.parse(this.dataset.config));
            });
        });

        document.querySelectorAll('.btn-test-sms').forEach(btn => {
            btn.addEventListener('click', function () {
                openTestModal('sms', this.dataset.id, this.dataset.name);
            });
        });

        document.querySelectorAll('.btn-test-whatsapp').forEach(btn => {
            btn.addEventListener('click', function () {
                openTestModal('whatsapp', this.dataset.id, this.dataset.name);
            });
        });

        document.querySelectorAll('.btn-test-email').forEach(btn => {
            btn.addEventListener('click', function () {
                openTestModal('email', this.dataset.id, this.dataset.name);
            });
        });
    </script>
@endpush

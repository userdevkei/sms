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
                        <table {{--id="smsGatewaysTable"--}} class="table table-hover align-middle w-100">
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
                        <table {{--id="paymentGatewaysTable"--}} class="table table-hover align-middle w-100">
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
                        <table {{--id="emailGatewaysTable" --}}class="table table-hover align-middle w-100">
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
                <div id="smsMethodField"></div>
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

    {{-- ============ PAYMENT GATEWAY MODAL ============ --}}
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="paymentForm" action="{{ route('settings.payment-gateways.store') }}">
                @csrf
                <div id="paymentMethodField"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalTitle">Add Payment Gateway</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="payment_name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Provider <span class="text-danger">*</span></label>
                            <select name="provider" id="payment_provider" class="form-select" required onchange="togglePaymentProviderFields()">
                                <option value="mpesa">M-Pesa (Daraja)</option>
                                <option value="bank_api">Bank API</option>
                            </select>
                        </div>

                        <div id="payment_mpesa_fields">
                            <div class="mb-2">
                                <label class="form-label">Environment <span class="text-danger">*</span></label>
                                <select name="environment" id="payment_environment" class="form-select">
                                    <option value="sandbox">Sandbox</option>
                                    <option value="live">Live</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Consumer Key</label>
                                <input type="password" name="consumer_key" id="payment_consumer_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_consumer_key_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Consumer Secret</label>
                                <input type="password" name="consumer_secret" id="payment_consumer_secret" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_consumer_secret_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Shortcode <span class="text-danger">*</span></label>
                                <input type="text" name="shortcode" id="payment_shortcode" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Passkey</label>
                                <input type="password" name="passkey" id="payment_passkey" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_passkey_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Callback URL <span class="text-danger">*</span></label>
                                <input type="url" name="callback_url" id="payment_callback_url" class="form-control">
                            </div>
                        </div>

                        <div id="payment_bank_api_fields" class="d-none">
                            <div class="mb-2">
                                <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                                <input type="text" name="bank_name" id="payment_bank_name" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">API Key</label>
                                <input type="password" name="api_key" id="payment_api_key" class="form-control" autocomplete="new-password">
                                <small class="text-muted" id="payment_api_key_hint"></small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">API Secret</label>
                                <input type="password" name="api_secret" id="payment_api_secret" class="form-control" autocomplete="new-password">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Account Number <span class="text-danger">*</span></label>
                                <input type="text" name="account_number" id="payment_account_number" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Endpoint URL <span class="text-danger">*</span></label>
                                <input type="url" name="endpoint_url" id="payment_endpoint_url" class="form-control">
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
                <div id="emailMethodField"></div>
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
                            <input type="text" name="username" id="email_username" class="form-control">
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

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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

        ['smsGatewaysTable', 'paymentGatewaysTable', 'emailGatewaysTable'].forEach(id => {
            if (document.getElementById(id)) {
                $('#' + id).DataTable({ order: [[0, 'asc']], pageLength: 10, columnDefs: [{ targets: -1, orderable: false }] });
            }
        });

        const routes = {
            smsStore: @json(route('settings.sms-gateways.store')),
            smsUpdate: id => @json(route('settings.sms-gateways.update', ['smsGateway' => '__ID__'])).replace('__ID__', id),
            paymentStore: @json(route('settings.payment-gateways.store')),
            paymentUpdate: id => @json(route('settings.payment-gateways.update', ['paymentGateway' => '__ID__'])).replace('__ID__', id),
            emailStore: @json(route('settings.email-gateways.store')),
            emailUpdate: id => @json(route('settings.email-gateways.update', ['emailGateway' => '__ID__'])).replace('__ID__', id),
            // NOT YET CONFIRMED — guessed URI pattern. Paste your actual route
            // list for activate/destroy and I will fix these two lines.
            activate: (type, id) => `/settings/${type}-gateways/${id}/activate`,
            destroy: (type, id) => `/settings/${type}-gateways/${id}`,
        };

        function toggleSmsProviderFields() {
            const isAt = document.getElementById('sms_provider').value === 'africas_talking';
            document.getElementById('sms_africas_talking_fields').classList.toggle('d-none', !isAt);
            document.getElementById('sms_custom_fields').classList.toggle('d-none', isAt);
        }

        function togglePaymentProviderFields() {
            const isMpesa = document.getElementById('payment_provider').value === 'mpesa';
            document.getElementById('payment_mpesa_fields').classList.toggle('d-none', !isMpesa);
            document.getElementById('payment_bank_api_fields').classList.toggle('d-none', isMpesa);
        }

        function openSmsModal(data) {
            const form = document.getElementById('smsForm');
            const isEdit = !!data;
            document.getElementById('smsModalTitle').textContent = isEdit ? 'Edit SMS Gateway' : 'Add SMS Gateway';
            form.action = isEdit ? routes.smsUpdate(data.id) : routes.smsStore;
            document.getElementById('smsMethodField').innerHTML = isEdit ? '@method('PATCH')' : '';

            document.getElementById('sms_name').value = data?.name ?? '';
            document.getElementById('sms_provider').value = data?.provider ?? 'africas_talking';
            document.getElementById('sms_username').value = data?.username ?? '';
            document.getElementById('sms_sender_id').value = data?.sender_id ?? '';
            document.getElementById('sms_endpoint_url').value = data?.endpoint_url ?? '';
            document.getElementById('sms_api_key_at').value = '';
            document.getElementById('sms_api_key_custom').value = '';

            const hint = isEdit ? 'Leave blank to keep the current key.' : '';
            document.getElementById('sms_api_key_at_hint').textContent = hint;
            document.getElementById('sms_api_key_custom_hint').textContent = hint;

            toggleSmsProviderFields();
            new bootstrap.Modal(document.getElementById('smsModal')).show();
        }

        function openPaymentModal(data) {
            const form = document.getElementById('paymentForm');
            const isEdit = !!data;
            document.getElementById('paymentModalTitle').textContent = isEdit ? 'Edit Payment Gateway' : 'Add Payment Gateway';
            form.action = isEdit ? routes.paymentUpdate(data.id) : routes.paymentStore;
            document.getElementById('paymentMethodField').innerHTML = isEdit ? '@method('PATCH')' : '';

            document.getElementById('payment_name').value = data?.name ?? '';
            document.getElementById('payment_provider').value = data?.provider ?? 'mpesa';
            document.getElementById('payment_environment').value = data?.environment ?? 'sandbox';
            document.getElementById('payment_shortcode').value = data?.shortcode ?? '';
            document.getElementById('payment_callback_url').value = data?.callback_url ?? "{{ route('mpesa.callback') }}";
            document.getElementById('payment_bank_name').value = data?.bank_name ?? '';
            document.getElementById('payment_account_number').value = data?.account_number ?? '';
            document.getElementById('payment_endpoint_url').value = data?.endpoint_url ?? '';

            ['consumer_key', 'consumer_secret', 'passkey', 'api_key', 'api_secret'].forEach(f => {
                document.getElementById('payment_' + f).value = '';
            });
            const hint = isEdit ? 'Leave blank to keep the current value.' : '';
            ['payment_consumer_key_hint', 'payment_consumer_secret_hint', 'payment_passkey_hint', 'payment_api_key_hint'].forEach(id => {
                document.getElementById(id).textContent = hint;
            });

            togglePaymentProviderFields();
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        function openEmailModal(data) {
            const form = document.getElementById('emailForm');
            const isEdit = !!data;
            document.getElementById('emailModalTitle').textContent = isEdit ? 'Edit Email Gateway' : 'Add Email Gateway';
            form.action = isEdit ? routes.emailUpdate(data.id) : routes.emailStore;
            document.getElementById('emailMethodField').innerHTML = isEdit ? '@method('PATCH')' : '';

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

        document.querySelectorAll('.btn-edit-sms').forEach(btn => {
            btn.addEventListener('click', function () {
                openSmsModal(JSON.parse(this.dataset.config));
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
    </script>
@endpush

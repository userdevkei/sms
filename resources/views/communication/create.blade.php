{{-- resources/views/communication/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Send Message')

@section('content')
    <h1 class="h4 mb-4"><i class="bi bi-send me-2"></i>Send Message</h1>

    <form method="POST" action="{{ route('communication.store') }}" class="card border-0 shadow-sm" id="composeForm">
        @csrf
        <div class="card-body p-4">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Use a template (optional)</label>
                    <select id="tplSelect" class="form-select">
                        <option value="">— Write a custom message —</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="communication_template_id" id="communication_template_id">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channel <span class="text-danger">*</span></label>
                    <select name="channel" id="channelSelect" class="form-select" required>
                        <option value="sms">SMS</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="col-12">
                    <small class="text-muted">Picking a template fills in the channel, subject, and message below — you can still edit them before sending.</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Subject (email only)</label>
                <input type="text" name="subject" id="subjectInput" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Message <span class="text-danger">*</span></label>
                <textarea name="body" id="bodyInput" class="form-control" rows="4" required></textarea>
            </div>

            <hr class="my-4">

            {{-- ============ AUDIENCE ============ --}}
            <div class="mb-3">
                <label class="form-label d-block">Send to <span class="text-danger">*</span></label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check audience-type" name="audience_type" id="aud_manual" value="manual" autocomplete="off" checked>
                    <label class="btn btn-outline-primary" for="aud_manual"><i class="bi bi-pencil-square me-1"></i>Manual</label>

                    <input type="radio" class="btn-check audience-type" name="audience_type" id="aud_students" value="students" autocomplete="off">
                    <label class="btn btn-outline-primary" for="aud_students"><i class="bi bi-mortarboard me-1"></i>Students</label>

                    <input type="radio" class="btn-check audience-type" name="audience_type" id="aud_staff" value="staff" autocomplete="off">
                    <label class="btn btn-outline-primary" for="aud_staff"><i class="bi bi-person-badge me-1"></i>Staff</label>

                    <input type="radio" class="btn-check audience-type" name="audience_type" id="aud_guardians" value="guardians" autocomplete="off">
                    <label class="btn btn-outline-primary" for="aud_guardians"><i class="bi bi-people me-1"></i>Guardians</label>
                </div>
            </div>

            {{-- ---- MANUAL ---- --}}
            <div id="audience_manual_panel" class="mb-3 border rounded-3 p-3">
                <label class="form-label">Recipients <span class="text-danger">*</span></label>
                <textarea name="recipients" class="form-control" rows="3" placeholder="One phone number or email per line"></textarea>
            </div>

            {{-- ---- STUDENTS ---- --}}
            <div id="audience_students_panel" class="d-none mb-3 border rounded-3 p-3">
                <label class="form-label d-block">Which students?</label>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input students-mode" type="radio" name="students_mode" value="all" id="students_mode_all" checked>
                        <label class="form-check-label" for="students_mode_all">All students</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input students-mode" type="radio" name="students_mode" value="grade" id="students_mode_grade">
                        <label class="form-check-label" for="students_mode_grade">By grade level</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input students-mode" type="radio" name="students_mode" value="specific" id="students_mode_specific">
                        <label class="form-check-label" for="students_mode_specific">Select specific students</label>
                    </div>
                </div>

                <div id="students_grade_fields" class="d-none mb-2">
                    <label class="form-label">Grade level(s)</label>
                    <select name="grade_level_id[]" id="gradeLevelPicker" class="form-select select2-multi" multiple>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="students_specific_fields" class="d-none">
                    <select name="student_ids[]" id="students_picker" class="form-select select2-multi" multiple>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ---- STAFF ---- --}}
            <div id="audience_staff_panel" class="d-none mb-3 border rounded-3 p-3">
                <label class="form-label d-block">Which staff?</label>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="staff_mode" value="all" id="staff_mode_all" checked>
                        <label class="form-check-label" for="staff_mode_all">All staff</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="staff_mode" value="specific" id="staff_mode_specific">
                        <label class="form-check-label" for="staff_mode_specific">Select specific staff</label>
                    </div>
                </div>

                <div id="staff_specific_fields" class="d-none">
                    <select name="staff_ids[]" id="staff_picker" class="form-select select2-multi" multiple>
                        @foreach($staffMembers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ---- GUARDIANS ---- --}}
            <div id="audience_guardians_panel" class="d-none mb-3 border rounded-3 p-3">
                <label class="form-label d-block">Which guardians?</label>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="guardians_mode" value="all" id="guardians_mode_all" checked>
                        <label class="form-check-label" for="guardians_mode_all">All guardians</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="guardians_mode" value="specific" id="guardians_mode_specific">
                        <label class="form-check-label" for="guardians_mode_specific">Select specific guardians</label>
                    </div>
                </div>

                <div id="guardians_specific_fields" class="d-none">
                    <select name="guardian_ids[]" id="guardians_picker" class="form-select select2-multi" multiple>
                        @foreach($guardians as $g)
                            <option value="{{ $g->id }}">{{ $g->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Send at (leave blank to send now)</label>
                    <input type="datetime-local" name="send_at" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Repeat</label>
                    <select name="recurrence_rule" class="form-select">
                        <option value="">Don't repeat</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 p-4 pt-0 d-flex justify-content-end">
            <button type="submit" class="btn btn-sm btn-primary px-4"><i class="bi bi-send-check me-1"></i> Send</button>
        </div>
    </form>

    {{-- Full template content, per channel, embedded once so the template picker never needs a round-trip. --}}
    <script id="templatesData" type="application/json">
        {!! $templates->map(fn ($t) => [
            'id' => $t->id,
            'channels' => $t->channels,
            'subject' => $t->subject,
            'sms_body' => $t->sms_body,
            'email_body' => $t->email_body,
            'whatsapp_body' => $t->whatsapp_body,
        ])->values()->toJson() !!}
    </script>
@endsection
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
@push('scripts')
    <script>
        $('.select2-multi').select2({
            theme: 'bootstrap-5',
            width: '100%',
        });

        const TEMPLATES = JSON.parse(document.getElementById('templatesData').textContent);

        // ---- Template auto-fill ----
        document.getElementById('tplSelect').addEventListener('change', function () {
            const tpl = TEMPLATES.find(t => String(t.id) === this.value);
            document.getElementById('communication_template_id').value = this.value || '';

            if (!tpl) return;

            // Restrict the channel dropdown to only what the template supports, default to the first.
            const channelSelect = document.getElementById('channelSelect');
            Array.from(channelSelect.options).forEach(opt => {
                opt.disabled = !tpl.channels.includes(opt.value);
            });
            if (!tpl.channels.includes(channelSelect.value)) {
                channelSelect.value = tpl.channels[0];
            }

            fillBodyForChannel(tpl, channelSelect.value);
        });

        document.getElementById('channelSelect').addEventListener('change', function () {
            const tplId = document.getElementById('communication_template_id').value;
            const tpl = TEMPLATES.find(t => String(t.id) === tplId);
            if (tpl) fillBodyForChannel(tpl, this.value);
        });

        function fillBodyForChannel(tpl, channel) {
            const bodyMap = { sms: tpl.sms_body, whatsapp: tpl.whatsapp_body, email: tpl.email_body };
            document.getElementById('bodyInput').value = bodyMap[channel] ?? '';
            document.getElementById('subjectInput').value = channel === 'email' ? (tpl.subject ?? '') : '';
        }

        // ---- Audience type switcher ----
        const AUDIENCE_PANELS = {
            manual: 'audience_manual_panel',
            students: 'audience_students_panel',
            staff: 'audience_staff_panel',
            guardians: 'audience_guardians_panel',
        };

        document.querySelectorAll('.audience-type').forEach(radio => {
            radio.addEventListener('change', function () {
                Object.entries(AUDIENCE_PANELS).forEach(([type, panelId]) => {
                    const panel = document.getElementById(panelId);
                    const isActive = type === this.value;
                    panel.classList.toggle('d-none', !isActive);
                    panel.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = !isActive; });
                });
            });
        });
        // Manual panel is the default-checked one — disable the others' inputs up front.
        document.querySelectorAll('.audience-type:not(:checked)').forEach(radio => {
            document.querySelectorAll('#' + AUDIENCE_PANELS[radio.value] + ' input, #' + AUDIENCE_PANELS[radio.value] + ' select, #' + AUDIENCE_PANELS[radio.value] + ' textarea')
                .forEach(el => { el.disabled = true; });
        });

        // ---- Students sub-mode switcher (all / grade / specific) ----
        document.querySelectorAll('.students-mode').forEach(radio => {
            radio.addEventListener('change', function () {
                const showGrade = this.value === 'grade';
                const showSpecific = this.value === 'specific';
                toggleFieldset('students_grade_fields', showGrade);
                toggleFieldset('students_specific_fields', showSpecific);
            });
        });

        document.getElementById('staff_mode_specific').addEventListener('change', () => toggleFieldset('staff_specific_fields', true));
        document.getElementById('staff_mode_all').addEventListener('change', () => toggleFieldset('staff_specific_fields', false));

        document.getElementById('guardians_mode_specific').addEventListener('change', () => toggleFieldset('guardians_specific_fields', true));
        document.getElementById('guardians_mode_all').addEventListener('change', () => toggleFieldset('guardians_specific_fields', false));

        function toggleFieldset(id, show) {
            const el = document.getElementById(id);
            el.classList.toggle('d-none', !show);
            el.querySelectorAll('input, select').forEach(field => { field.disabled = !show; });
        }

        // ---- Filter box above each multi-select (client-side, no AJAX) ----
        document.querySelectorAll('.picker-filter').forEach(input => {
            input.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                const select = document.getElementById(this.dataset.target);
                Array.from(select.options).forEach(opt => {
                    opt.hidden = term.length > 0 && !opt.textContent.toLowerCase().includes(term);
                });
            });
        });
    </script>
@endpush

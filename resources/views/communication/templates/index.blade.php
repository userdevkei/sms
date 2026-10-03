{{-- resources/views/communication/templates/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Message Templates')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><i class="bi bi-file-text me-2"></i>Message Templates</h1>
        <button type="button" class="btn btn-sm btn-primary" onclick="openTemplateModal()">
            <i class="bi bi-plus-lg me-1"></i> New Template
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="templatesTable" class="table table-hover table-sm table-striped fs-sm w-100">
                    <thead><tr><th>#</th><th>Name</th><th>Trigger</th><th>Channels</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach($templates as $t)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $t->name }}</td>
                            <td>{{ \App\Models\CommunicationTemplate::TRIGGER_KEYS[$t->trigger_key] ?? '— manual only —' }}</td>
                            <td>
                                @foreach($t->channels as $c)
                                    <span class="badge bg-light border text-dark text-capitalize">{{ $c }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($t->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-template"
                                        data-config="{{ json_encode($t->only(['id','name','trigger_key','channels','subject','sms_body','email_body','whatsapp_body','is_active'])) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTemplate('{{ $t->id }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <form method="POST" id="templateForm" action="{{ route('communication.templates.store') }}">
                @csrf
                <input type="hidden" name="_method" id="templateMethodField" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="templateModalTitle">New Template</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="tpl_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Trigger (optional — leave blank for manual-use only)</label>
                                <select name="trigger_key" id="tpl_trigger_key" class="form-select">
                                    <option value="">— None (manual use only) —</option>
                                    @foreach(\App\Models\CommunicationTemplate::TRIGGER_KEYS as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Send via <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input tpl-channel" type="checkbox" name="channels[]" value="sms" id="tpl_ch_sms" onchange="toggleTemplateChannelFields()">
                                <label class="form-check-label" for="tpl_ch_sms">SMS</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input tpl-channel" type="checkbox" name="channels[]" value="email" id="tpl_ch_email" onchange="toggleTemplateChannelFields()">
                                <label class="form-check-label" for="tpl_ch_email">Email</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input tpl-channel" type="checkbox" name="channels[]" value="whatsapp" id="tpl_ch_whatsapp" onchange="toggleTemplateChannelFields()">
                                <label class="form-check-label" for="tpl_ch_whatsapp">WhatsApp</label>
                            </div>
                        </div>

                        {{-- ---- SMS ---- --}}
                        <div id="tpl_sms_fields" class="d-none mb-3 border rounded-3 p-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>SMS message</span>
                                <span class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-braces"></i> Insert variable
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="contact_name">Contact name</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="student_name">Student name</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="student_number">Student Number</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="balance">Fee Balance</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="reference">Reference</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="code">Code</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="status">Status</a></li>
                                    </ul>
                                </span>
                            </label>
                            <textarea name="sms_body" id="tpl_sms_body" class="form-control" rows="3" maxlength="1000"></textarea>
                            <small class="text-muted">Plain text only — SMS has no formatting.</small>
                        </div>

                        {{-- ---- EMAIL (Word-style rich editor) ---- --}}
                        <div id="tpl_email_fields" class="d-none mb-3 border rounded-3 p-3">
                            <label class="form-label">Email subject</label>
                            <input type="text" name="subject" id="tpl_subject" class="form-control mb-3">

                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Email body</span>
                                <span class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-braces"></i> Insert variable
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_email_editor" data-var="contact_name">Contact name</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="student_name">Student name</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="student_number">Student Number</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="balance">Fee Balance</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_email_editor" data-var="reference">Reference</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_email_editor" data-var="code">Code</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_email_editor" data-var="status">Status</a></li>
                                    </ul>
                                </span>
                            </label>

                            <div class="wysiwyg-toolbar btn-toolbar mb-1" role="toolbar">
                                <div class="btn-group btn-group-sm me-1" role="group">
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="italic" title="Italic"><i class="bi bi-type-italic"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="underline" title="Underline"><i class="bi bi-type-underline"></i></button>
                                </div>
                                <div class="btn-group btn-group-sm me-1" role="group">
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="insertUnorderedList" title="Bullet list"><i class="bi bi-list-ul"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="insertOrderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="justifyLeft" title="Align left"><i class="bi bi-text-left"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="justifyCenter" title="Align center"><i class="bi bi-text-center"></i></button>
                                    <button type="button" class="btn btn-outline-secondary" data-wysiwyg-cmd="removeFormat" title="Clear formatting"><i class="bi bi-eraser"></i></button>
                                </div>
                            </div>

                            {{-- Word-style page: white card, shadow, generous padding, max-width like a document --}}
                            <div class="wysiwyg-page">
                                <div id="tpl_email_editor" class="wysiwyg-editor" contenteditable="true"></div>
                            </div>
                            {{-- Hidden field actually submitted — synced from the editor above right before submit --}}
                            <textarea name="email_body" id="tpl_email_body" class="d-none"></textarea>
                        </div>

                        {{-- ---- WHATSAPP ---- --}}
                        <div id="tpl_whatsapp_fields" class="d-none mb-2 border rounded-3 p-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>WhatsApp message</span>
                                <span class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-braces"></i> Insert variable
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_whatsapp_body" data-var="contact_name">Contact name</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="student_name">Student name</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="student_number">Student Number</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_sms_body" data-var="balance">Fee Balance</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_whatsapp_body" data-var="reference">Reference</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_whatsapp_body" data-var="code">Code</a></li>
                                        <li><a class="dropdown-item tpl-var-insert" href="#" data-target="tpl_whatsapp_body" data-var="status">Status</a></li>
                                    </ul>
                                </span>
                            </label>
                            <textarea name="whatsapp_body" id="tpl_whatsapp_body" class="form-control" rows="3" maxlength="1000"></textarea>
                            <small class="text-muted">Plain text only — WhatsApp Cloud API free-form messages don't render HTML.</small>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="tpl_is_active" checked>
                            <label class="form-check-label" for="tpl_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="templateSubmitBtn">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        .wysiwyg-page {
            background: #f1f3f5;
            border-radius: .5rem;
            padding: 1.25rem;
        }
        .wysiwyg-editor {
            background: #fff;
            max-width: 100%;
            min-height: 220px;
            margin: 0 auto;
            padding: 1.5rem 1.75rem;
            border: 1px solid #d8deea;
            border-radius: .35rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
            font-family: 'Segoe UI', ui-sans-serif, system-ui, sans-serif;
            font-size: .95rem;
            line-height: 1.6;
            color: #212529;
        }
        .wysiwyg-editor:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .15);
        }
        .wysiwyg-editor ul, .wysiwyg-editor ol { padding-left: 1.5rem; }
        .tpl-var-token {
            background: rgba(var(--bs-primary-rgb), .1);
            color: var(--bs-primary);
            border-radius: .25rem;
            padding: 0 .3rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .85em;
        }
    </style>
@endpush
<script>
    window.routes = {
        templateStore:   @json(route('communication.templates.store')),
        templateUpdate:  @json(route('communication.templates.update', ['template' => '__ID__'])),
        templateDestroy: @json(route('communication.templates.destroy', ['template' => '__ID__'])),
    };
</script>
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Built via concatenation, never written as literal adjacent braces, so Blade's
        // as a PHP echo (this bit us twice already — see OPEN_TAG/CLOSE_TAG below).
        const OPEN_TAG = '{' + '{';
        const CLOSE_TAG = '}' + '}';

        $('#templatesTable').DataTable({ order: [[0, 'asc']], pageLength: 10, columnDefs: [{ targets: -1, orderable: false }] });

        function toggleTemplateChannelFields() {
            const map = { sms: 'tpl_sms_fields', email: 'tpl_email_fields', whatsapp: 'tpl_whatsapp_fields' };
            ['sms', 'email', 'whatsapp'].forEach(c => {
                const checked = document.getElementById('tpl_ch_' + c).checked;
                const fieldset = document.getElementById(map[c]);
                fieldset.classList.toggle('d-none', !checked);
                fieldset.querySelectorAll('input, textarea, [contenteditable]').forEach(el => {
                    if (el.hasAttribute('contenteditable')) {
                        el.contentEditable = checked ? 'true' : 'false';
                    } else {
                        el.disabled = !checked;
                    }
                });
            });
        }

        document.querySelectorAll('[data-wysiwyg-cmd]').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('tpl_email_editor').focus();
                document.execCommand(this.dataset.wysiwygCmd, false, null);
            });
        });

        document.querySelectorAll('.tpl-var-insert').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const token = OPEN_TAG + this.dataset.var + CLOSE_TAG;
                const target = document.getElementById(this.dataset.target);

                if (target.hasAttribute('contenteditable')) {
                    target.focus();
                    const span = '<span class="tpl-var-token" contenteditable="false">' + token + '</span>&nbsp;';
                    document.execCommand('insertHTML', false, span);
                } else {
                    insertAtCursor(target, token);
                }
            });
        });

        function insertAtCursor(textarea, text) {
            textarea.focus();
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
            const pos = start + text.length;
            textarea.setSelectionRange(pos, pos);
        }

        function serializeEmailEditor() {
            const clone = document.getElementById('tpl_email_editor').cloneNode(true);
            clone.querySelectorAll('.tpl-var-token').forEach(span => {
                span.replaceWith(document.createTextNode(span.textContent));
            });
            document.getElementById('tpl_email_body').value = clone.innerHTML;
        }

        document.getElementById('templateForm').addEventListener('submit', function () {
            if (!document.getElementById('tpl_ch_email').disabled) {
                serializeEmailEditor();
            }
        });

        function renderIntoEditor(html) {
            const editor = document.getElementById('tpl_email_editor');
            const pattern = new RegExp(OPEN_TAG + '\\s*(\\w+)\\s*' + CLOSE_TAG, 'g');

            editor.innerHTML = (html ?? '').replace(
                pattern,
                (match, name) => '<span class="tpl-var-token" contenteditable="false">' + OPEN_TAG + name + CLOSE_TAG + '</span>'
            );
        }

        function openTemplateModal(data) {
            const form = document.getElementById('templateForm');
            const isEdit = !!data;

            document.getElementById('templateModalTitle').textContent = isEdit ? 'Edit Template' : 'New Template';

            form.action = isEdit
                ? window.routes.templateUpdate.replace('__ID__', data.id)
                : window.routes.templateStore;

            document.getElementById('templateMethodField').value = isEdit ? 'PATCH' : '';

            document.getElementById('tpl_name').value = data?.name ?? '';
            document.getElementById('tpl_trigger_key').value = data?.trigger_key ?? '';
            document.getElementById('tpl_subject').value = data?.subject ?? '';
            document.getElementById('tpl_sms_body').value = data?.sms_body ?? '';
            document.getElementById('tpl_whatsapp_body').value = data?.whatsapp_body ?? '';
            document.getElementById('tpl_is_active').checked = data?.is_active ?? true;
            renderIntoEditor(data?.email_body ?? '');

            const channels = data?.channels ?? [];
            document.getElementById('tpl_ch_sms').checked = channels.includes('sms');
            document.getElementById('tpl_ch_email').checked = channels.includes('email');
            document.getElementById('tpl_ch_whatsapp').checked = channels.includes('whatsapp');

            toggleTemplateChannelFields();

            new bootstrap.Modal(document.getElementById('templateModal')).show();
        }

        function deleteTemplate(id) {
            if (!confirm('Delete this template?')) return;

            const url = window.routes.templateDestroy.replace('__ID__', id);

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(res => res.success ? location.reload() : alert('Delete failed.'))
                .catch(err => {
                    console.error('Delete template failed:', err);
                    alert('Delete failed: ' + err.message);
                });
        }

        document.querySelectorAll('.btn-edit-template').forEach(btn => {
            btn.addEventListener('click', function () { openTemplateModal(JSON.parse(this.dataset.config)); });
        });
    </script>
@endpush

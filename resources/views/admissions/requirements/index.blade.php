@extends('layouts.app')

@section('content')
<style>
    .adm { --a-border: #e8ecf3; --a-muted: #64748b; }
    .adm .card { border: 1px solid var(--a-border); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .adm .card-header { background: transparent; border-bottom: 1px solid var(--a-border); padding: .9rem 1.25rem; }
    .adm-levels { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .25rem; }
    .adm-levels a { border: 1px solid var(--a-border); background: #fff; border-radius: 999px; padding: .4rem 1rem; font-size: .88rem; font-weight: 600; color: #334155; white-space: nowrap; text-decoration: none; }
    .adm-levels a:hover { background: #f8fafc; }
    .adm-levels a.is-active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    .adm-req { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f0f3f8; }
    .adm-req:last-child { border-bottom: 0; }
    .adm-req .order { display: flex; flex-direction: column; gap: 2px; }
    .adm-req .order button { border: 0; background: transparent; color: #94a3b8; padding: 0 .25rem; line-height: 1; }
    .adm-req .order button:hover:not(:disabled) { color: var(--bs-primary); }
    .adm-req .order button:disabled { opacity: .3; }
</style>

<div class="adm container-fluid py-4" id="reqApp">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('admissions.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i>Admissions</a>
            <h4 class="fw-bold mt-1 mb-1">Requirements &amp; settings</h4>
            <p class="text-muted mb-0">Decide what each education level asks applicants for, and whether an interview is needed.</p>
        </div>
    </div>

    @foreach (['adm_success' => 'success', 'adm_error' => 'danger'] as $flash => $tone)
        @if (session($flash))
            <div class="alert alert-{{ $tone }}">{{ session($flash) }}</div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>
    @endif

    <div class="adm-levels mb-4">
        @foreach ($levels as $l)
            <a href="{{ route('admissions.requirements.index', ['level' => $l->id]) }}" class="{{ $level && $level->id === $l->id ? 'is-active' : '' }}">{{ $l->name }}</a>
        @endforeach
    </div>

    @if (! $level)
        <div class="alert alert-info">Create an education level first (Curriculum → Education levels).</div>
    @else
        <div class="row g-4">
            {{-- Level settings --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header fw-bold">{{ $level->name }} settings</div>
                    <form method="POST" action="{{ route('admissions.requirements.settings', $level->id) }}" class="card-body">
                        @csrf @method('PUT')

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_open" name="is_open" value="1" @checked($setting->is_open)>
                            <label class="form-check-label fw-semibold" for="is_open">Accepting applications</label>
                            <div class="form-text">Turn off to hide this level from the public form.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="requires_interview" name="requires_interview" value="1" @checked($setting->requires_interview)>
                            <label class="form-check-label fw-semibold" for="requires_interview">Interview required</label>
                            <div class="form-text">Approved applicants must pass an interview before they can be admitted.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="instructions">Instructions for applicants</label>
                            <textarea id="instructions" name="instructions" rows="4" class="form-control" placeholder="Shown at the documents step, e.g. bring originals to the interview.">{{ $setting->instructions }}</textarea>
                        </div>

                        <button class="btn btn-sm btn-primary w-100">Save settings</button>
                    </form>
                </div>
            </div>

            {{-- Requirements --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold">What applicants must provide <span class="text-muted fw-normal">({{ $requirements->count() }})</span></span>
                        <button type="button" class="btn btn-sm btn-primary btn-sm" id="btnAdd"><i class="bi bi-plus-lg me-1"></i>Add requirement</button>
                    </div>

                    @forelse ($requirements as $req)
                        @php
                            $config = [
                                'update_url'         => route('admissions.requirements.update', $req->id),
                                'label'              => $req->label,
                                'type'               => $req->type,
                                'is_required'        => $req->is_required,
                                'help_text'          => $req->help_text,
                                'options_text'       => implode("\n", $req->options ?? []),
                                'allowed_extensions' => $req->allowed_extensions,
                                'max_size_kb'        => $req->max_size_kb,
                            ];
                        @endphp
                        <div class="adm-req">
                            <div class="order">
                                <form method="POST" action="{{ route('admissions.requirements.move', $req->id) }}">@csrf<input type="hidden" name="direction" value="up">
                                    <button type="submit" @disabled($loop->first) title="Move up"><i class="bi bi-chevron-up"></i></button></form>
                                <form method="POST" action="{{ route('admissions.requirements.move', $req->id) }}">@csrf<input type="hidden" name="direction" value="down">
                                    <button type="submit" @disabled($loop->last) title="Move down"><i class="bi bi-chevron-down"></i></button></form>
                            </div>

                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $req->label }}
                                    <span class="badge {{ $req->is_required ? 'text-bg-danger-subtle text-danger-emphasis' : 'text-bg-light border text-muted' }} ms-1">{{ $req->is_required ? 'Required' : 'Optional' }}</span>
                                </div>
                                <div class="small text-muted">
                                    <i class="bi {{ $req->isFile() ? 'bi-file-earmark-arrow-up' : 'bi-input-cursor-text' }} me-1"></i>{{ $req->typeLabel() }}
                                    @if ($req->isFile())
                                        &middot; {{ strtoupper(implode(', ', $req->extensions())) }} &middot; max {{ rtrim(rtrim(number_format($req->max_size_kb / 1024, 1), '0'), '.') }} MB
                                    @elseif ($req->type === 'select')
                                        &middot; {{ implode(', ', $req->options ?? []) }}
                                    @endif
                                </div>
                                @if ($req->help_text)<div class="small text-muted fst-italic">{{ $req->help_text }}</div>@endif
                            </div>

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit" data-config='@json($config)' title="Edit"><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="{{ route('admissions.requirements.destroy', $req->id) }}" data-confirm="Remove &quot;{{ $req->label }}&quot;? Answers already collected are kept.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-list-check fs-1 d-block mb-2"></i>
                            <div class="fw-semibold text-body">No requirements yet for {{ $level->name }}</div>
                            <div class="small mb-3">Add things like a NEMIS number, a birth certificate or a previous report card.</div>
                            <button type="button" class="btn btn-primary btn-sm" id="btnAddEmpty"><i class="bi bi-plus-lg me-1"></i>Add the first requirement</button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Add / edit modal --}}
        <div class="modal fade" id="reqModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="reqForm" class="modal-content" action="{{ route('admissions.requirements.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="reqMethod" value="POST" disabled>
                    <input type="hidden" name="education_level_id" value="{{ $level->id }}">

                    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold" id="reqTitle">Add requirement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="req_label">Label <span class="text-danger">*</span></label>
                            <input id="req_label" name="label" class="form-control" maxlength="150" required placeholder="e.g. NEMIS number, Birth certificate">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" for="req_type">Answer type</label>
                                <select id="req_type" name="type" class="form-select">
                                    @foreach (\App\Models\AdmissionRequirement::TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="req_required" name="is_required" value="1" checked>
                                    <label class="form-check-label" for="req_required">Required</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="req_help">Help text <span class="text-muted fw-normal">(optional)</span></label>
                            <input id="req_help" name="help_text" class="form-control" maxlength="255" placeholder="Shown under the label to guide the applicant">
                        </div>

                        <div class="mb-3 d-none" data-for="select">
                            <label class="form-label fw-semibold" for="req_options">Choices <span class="text-muted fw-normal">(one per line)</span></label>
                            <textarea id="req_options" name="options_text" rows="4" class="form-control"></textarea>
                        </div>

                        <div class="row g-3 d-none" data-for="file">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" for="req_ext">Allowed file types</label>
                                <input id="req_ext" name="allowed_extensions" class="form-control" placeholder="pdf, jpg, png">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="req_size">Max size (KB)</label>
                                <input id="req_size" type="number" name="max_size_kb" class="form-control" min="100" max="10240" value="2048">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm btn-primary" id="reqSubmit">Add requirement</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modalEl   = document.getElementById('reqModal');
    if (!modalEl) return;

    const modal     = bootstrap.Modal.getOrCreateInstance(modalEl);
    const $form     = $('#reqForm');
    const storeUrl  = $form.attr('action');

    const syncType = () => {
        const type = $('#req_type').val();
        $('[data-for]').each(function () { $(this).toggleClass('d-none', $(this).data('for') !== type); });
    };
    $('#req_type').on('change', syncType);

    const openAdd = () => {
        $form.attr('action', storeUrl);
        $('#reqMethod').prop('disabled', true);
        $('#reqTitle').text('Add requirement');
        $('#reqSubmit').text('Add requirement');
        $form[0].reset();
        $('#req_required').prop('checked', true);
        $('#req_size').val(2048);
        syncType();
        modal.show();
    };

    $('#btnAdd, #btnAddEmpty').on('click', openAdd);

    $(document).on('click', '.btn-edit', function () {
        const c = JSON.parse($(this).attr('data-config'));

        $form.attr('action', c.update_url);
        $('#reqMethod').val('PUT').prop('disabled', false);
        $('#reqTitle').text('Edit requirement');
        $('#reqSubmit').text('Save changes');

        $('#req_label').val(c.label);
        $('#req_type').val(c.type);
        $('#req_required').prop('checked', !!c.is_required);
        $('#req_help').val(c.help_text || '');
        $('#req_options').val(c.options_text || '');
        $('#req_ext').val(c.allowed_extensions || '');
        $('#req_size').val(c.max_size_kb || 2048);

        syncType();
        modal.show();
    });

    $(document).on('submit', 'form[data-confirm]', function (e) {
        if (!confirm($(this).data('confirm'))) e.preventDefault();
    });
});
</script>
@endpush

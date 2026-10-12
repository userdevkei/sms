@php
    $canManage = auth()->user()->hasPermission('users.update');

    $links = \Illuminate\Support\Facades\DB::table('guardian_student as gs')
        ->join('users as s', 's.id', '=', 'gs.student_id')
        ->leftJoin('student_enrollments as e', function ($j) {
            $j->on('e.user_id', '=', 's.id')->where('e.status', 'active')->whereNull('e.deleted_at');
        })
        ->leftJoin('grade_levels as g', 'g.id', '=', 'e.grade_level_id')
        ->leftJoin('streams as st', 'st.id', '=', 'e.stream_id')
        ->where('gs.guardian_id', $user->id)
        ->whereNull('s.deleted_at')
        ->orderByDesc('gs.is_primary')->orderBy('s.first_name')
        ->selectRaw("gs.student_id, gs.relationship, gs.is_primary, gs.can_pay, gs.status,
                     s.userID as adm, CONCAT_WS(' ', s.first_name, NULLIF(s.middle_name, ''), s.last_name) as name,
                     g.name as grade, st.name as stream")
        ->get();

    $relationships = ['Father', 'Mother', 'Guardian', 'Sibling', 'Relative', 'Other'];
@endphp

<div class="kv-card kv-panel mb-4">
    <div class="kv-panel-head d-flex justify-content-between align-items-center">
        <h3>Linked Students <span class="text-muted">({{ $links->count() }})</span></h3>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Relationship</th>
                <th>Flags</th>
                @if($canManage)<th class="text-end">Actions</th>@endif
            </tr>
            </thead>
            <tbody>
            @forelse($links as $l)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $l->name }}</div>
                        <small class="text-muted">{{ $l->adm }}</small>
                    </td>
                    <td>{{ trim(($l->grade ?? '') . ' ' . ($l->stream ?? '')) ?: '—' }}</td>
                    <td>{{ $l->relationship ?: '—' }}</td>
                    <td>
                        @if($l->is_primary)<span class="badge bg-primary">Primary</span>@endif
                        <span class="badge {{ $l->can_pay ? 'bg-success' : 'bg-secondary' }}">{{ $l->can_pay ? 'Can pay' : 'No pay' }}</span>
                        <span class="badge {{ $l->status === 'active' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ ucfirst($l->status) }}</span>
                    </td>
                    @if($canManage)
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#edit-link-{{ $l->student_id }}">Edit</button>
                            <form method="POST" class="d-inline"
                                  action="{{ route('users.guardian-students.destroy', [$user->id, $l->student_id]) }}"
                                  onsubmit="return confirm('Unlink {{ e($l->name) }} from this guardian?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Unlink</button>
                            </form>
                        </td>
                    @endif
                </tr>
                @if($canManage)
                    <tr class="collapse" id="edit-link-{{ $l->student_id }}">
                        <td colspan="5" class="bg-light">
                            <form method="POST" class="row g-2 align-items-end"
                                  action="{{ route('users.guardian-students.update', [$user->id, $l->student_id]) }}">
                                @csrf @method('PATCH')
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Relationship</label>
                                    <select name="relationship" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach($relationships as $r)
                                            <option @selected($l->relationship === $r)>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="active" @selected($l->status === 'active')>Active</option>
                                        <option value="inactive" @selected($l->status === 'inactive')>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="p{{ $l->student_id }}" @checked($l->is_primary)>
                                        <label class="form-check-label small" for="p{{ $l->student_id }}">Primary guardian</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="can_pay" value="1" id="c{{ $l->student_id }}" @checked($l->can_pay)>
                                        <label class="form-check-label small" for="c{{ $l->student_id }}">Can pay</label>
                                    </div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-sm btn-primary">Save</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No students linked yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($canManage)
        <div class="kv-panel-body border-top">
            <h6 class="text-uppercase text-muted small mb-3">Link a student</h6>
            <form method="POST" action="{{ route('users.guardian-students.store', $user->id) }}" id="linkStudentForm" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="student_id" id="linkStudentId">

                <div class="col-md-5 position-relative">
                    <label class="form-label small mb-1">Search by name or admission no.</label>
                    <input type="text" id="linkStudentSearch" class="form-control form-control-sm" autocomplete="off" placeholder="Type at least 2 characters">
                    <div id="linkStudentResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20; max-height: 260px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Relationship</label>
                    <select name="relationship" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach($relationships as $r)<option>{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="newPrimary">
                        <label class="form-check-label small" for="newPrimary">Primary</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="can_pay" value="1" id="newCanPay" checked>
                        <label class="form-check-label small" for="newCanPay">Can pay</label>
                    </div>
                    <button class="btn btn-sm btn-primary ms-2" id="linkStudentBtn" disabled>Link</button>
                </div>
            </form>
        </div>
    @endif
</div>

@if($canManage)
    @push('scripts')
        <script>
            (function () {
                const url     = @json(route('users.guardian-students.search', $user->id));
                const input   = document.getElementById('linkStudentSearch');
                const results = document.getElementById('linkStudentResults');
                const hidden  = document.getElementById('linkStudentId');
                const btn     = document.getElementById('linkStudentBtn');
                let timer;

                const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

                input.addEventListener('input', () => {
                    hidden.value = ''; btn.disabled = true;
                    clearTimeout(timer);
                    const q = input.value.trim();
                    if (q.length < 2) { results.classList.add('d-none'); return; }

                    timer = setTimeout(async () => {
                        const res  = await fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                        const rows = await res.json();

                        results.innerHTML = rows.length
                            ? rows.map(r => `<button type="button" class="list-group-item list-group-item-action py-2" data-id="${esc(r.id)}" data-label="${esc(r.name)} (${esc(r.userID)})">
                                    <div class="fw-semibold">${esc(r.name)}</div>
                                    <small class="text-muted">${esc(r.userID)} · ${esc([r.grade, r.stream].filter(Boolean).join(' ')) || 'No active class'}</small>
                                </button>`).join('')
                            : '<div class="list-group-item text-muted small">No matching students.</div>';
                        results.classList.remove('d-none');
                    }, 300);
                });

                results.addEventListener('click', e => {
                    const item = e.target.closest('[data-id]');
                    if (!item) return;
                    hidden.value = item.dataset.id;
                    input.value  = item.dataset.label;
                    btn.disabled = false;
                    results.classList.add('d-none');
                });

                document.addEventListener('click', e => {
                    if (!results.contains(e.target) && e.target !== input) results.classList.add('d-none');
                });
            })();
        </script>
    @endpush
@endif

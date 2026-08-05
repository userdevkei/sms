@extends('layouts.app')
@section('title', 'Import Preview')

@section('content')
    <h1 class="h4 mb-1">Import Preview</h1>
    <p class="text-muted mb-3">
        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $validCount }} valid</span>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">{{ $invalidCount }} invalid</span>
        — only valid, checked rows will be imported.
    </p>

    <form method="POST" action="{{ route('users.import.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <button type="button" id="selectAllValid" class="btn btn-sm btn-outline-primary">Select all valid</button>
                    <button type="button" id="deselectAll" class="btn btn-sm btn-outline-secondary">Deselect all</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Row</th>
                            <th>User ID</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Last Name</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>County</th>
                            <th>Sub County</th>
                            <th>Ward</th>
                            <th>Issues</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $i => $entry)
                            <tr class="{{ $entry['valid'] ? '' : 'table-danger' }}">
                                <td>
                                    <input type="checkbox" name="selected[]" value="{{ $i }}"
                                           class="form-check-input row-check"
                                        {{ $entry['valid'] ? 'checked' : 'disabled' }}>
                                </td>
                                <td>{{ $entry['row'] }}</td>
                                <td>{{ $entry['data']['user_id'] }}</td>
                                <td>{{ $entry['data']['first_name'] }}</td>
                                <td>{{ $entry['data']['middle_name'] ?? '—' }}</td>
                                <td>{{ $entry['data']['last_name'] }}</td>
                                <td>{{ $entry['data']['gender'] ? ucfirst($entry['data']['gender']) : '—' }}</td>
                                <td>{{ $entry['data']['email'] }}</td>
                                <td>{{ $entry['data']['phone'] ?? '—' }}</td>
                                <td>{{ $entry['data']['county'] ?? '—' }}</td>
                                <td>{{ $entry['data']['sub_county'] ?? '—' }}</td>
                                <td>{{ $entry['data']['ward'] ?? '—' }}</td>
                                <td>
                                    @foreach($entry['errors'] as $error)
                                        <div class="small text-danger">{{ $error }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white border-0 d-flex justify-content-between">
                <a href="{{ route('users.import.create') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Upload a different file
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i> Import Selected
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('selectAllValid').addEventListener('click', function () {
                document.querySelectorAll('.row-check:not(:disabled)').forEach(cb => cb.checked = true);
            });
            document.getElementById('deselectAll').addEventListener('click', function () {
                document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
            });
        });
    </script>
@endpush

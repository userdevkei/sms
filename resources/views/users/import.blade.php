@extends('layouts.app')
@section('title', 'Import Learners')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <h1 class="h4 mb-3">Import Learners</h1>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-muted">
                        Upload a CSV or Excel file. The first row must contain column headers:
                        <code>user_id, first_name, middle_name, last_name, gender, email, phone, county, sub_county, ward</code>
                        (<code>middle_name</code>, <code>phone</code>, <code>county</code>, <code>sub_county</code> and <code>ward</code> are optional).
                    </p>

                    <a href="{{ route('users.import.template') }}" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> Download template
                    </a>

                    <form method="POST" action="{{ route('users.import.preview') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                            @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i> Preview Import
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

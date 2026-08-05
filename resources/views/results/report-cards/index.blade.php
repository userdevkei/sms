@extends('layouts.app')
@section('title', 'Report Cards')

@section('content')
    <div class="mb-3"><h1 class="h4 mb-1">Compile Report Cards</h1><p class="text-muted mb-0">Pulls every finalized subject result for a class and term into a ranked report card, ready for review before publishing.</p></div>

    <x-results-tabs active="report-cards" />

    <div class="card border-0 shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('results.report-cards.compile') }}" id="reportCardForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select name="stream_id" id="streamSelect" class="form-select select2-field" required>
                        <option value="">Select class</option>
                        @foreach($streams as $stream)<option value="{{ $stream->id }}">{{ $stream->full_name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Term <span class="text-danger">*</span></label>
                    <select name="academic_term_id" id="termSelect" class="form-select select2-field" required>
                        <option value="">Select term</option>
                        @foreach($academicTerms as $term)<option value="{{ $term->id }}">{{ $term->academic_year }} - Term {{ $term->term_number }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Exam / Assessment <span class="text-danger">*</span></label>
                    <select name="exam" id="examSelect" class="form-select select2-field" required disabled>
                        <option value="">Select class and term first</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Continue</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-3" style="max-width: 560px;">
        <div class="card-body">
            <h2 class="h6 mb-2">Year Report</h2>
            <p class="text-muted small mb-3">Combines published Term 1, 2, and 3 results for a class into one yearly view.</p>
            <form method="GET" id="yearForm">
                <div class="mb-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select id="yearStreamSelect" class="form-select select2-field" required>
                        <option value="">Select class</option>
                        @foreach($streams as $stream)<option value="{{ $stream->id }}">{{ $stream->full_name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <select id="yearSelect" class="form-select select2-field" required>
                        <option value="">Select year</option>
                        @foreach($academicYears as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">View Year Report</button>
            </form>
        </div>
    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    @php
        $assessmentReviewTemplateUrl = route('results.report-cards.assessment-review', ['__stream__', '__term__', '__name__']);
    @endphp
    <script>
        $('.select2-field').select2({theme: 'bootstrap-5', width: '100%'});

        const roundsUrl = @json(route('results.report-cards.assessment-rounds'));
        const compileUrl = @json(route('results.report-cards.compile'));
        // {stream}/{term}/{name} placeholders swapped in below once real values are known
{{--        const assessmentReviewTemplate = @json(route('results.report-cards.assessment-review', ['__stream__', '__term__', '__name__']));--}}
        const assessmentReviewTemplate = @json($assessmentReviewTemplateUrl);

        function loadExamOptions() {
            const streamId = $('#streamSelect').val();
            const termId = $('#termSelect').val();
            const $exam = $('#examSelect');

            if (!streamId || !termId) {
                $exam.prop('disabled', true).html('<option value="">Select class and term first</option>').trigger('change');
                return;
            }

            $exam.prop('disabled', true).html('<option value="">Loading...</option>').trigger('change');

            $.getJSON(roundsUrl, {stream_id: streamId, academic_term_id: termId}, function (res) {
                let options = '<option value="">Select exam</option>';
                options += '<option value="__term_average">Term Average (All Assessments)</option>';
                (res.rounds || []).forEach(function (name) {
                    options += `<option value="${name}">${name}</option>`;
                });
                $exam.prop('disabled', false).html(options).trigger('change');
            });
        }

        $('#streamSelect, #termSelect').on('change', loadExamOptions);

        $('#reportCardForm').on('submit', function (e) {
            const exam = $('#examSelect').val();
            const streamId = $('#streamSelect').val();
            const termId = $('#termSelect').val();

            if (!exam) return; // let native required validation handle it

            if (exam === '__term_average') {
                // Term average uses the existing compile flow - form already
                // posts to compileUrl with stream_id/academic_term_id, so let
                // it submit normally.
                return;
            }

            // Specific round: no compile/persistence step, just navigate
            // straight to the on-the-fly review screen.
            e.preventDefault();
            const url = assessmentReviewTemplate
                .replace('__stream__', streamId)
                .replace('__term__', termId)
                .replace('__name__', encodeURIComponent(exam));
            window.location.href = url;
        });

        const yearReviewTemplate = @json(route('results.report-cards.year-review', ['__stream__', '__year__']));

        $('#yearForm').on('submit', function (e) {
            e.preventDefault();
            const streamId = $('#yearStreamSelect').val();
            const year = $('#yearSelect').val();
            if (!streamId || !year) return;
            window.location.href = yearReviewTemplate.replace('__stream__', streamId).replace('__year__', encodeURIComponent(year));
        });
    </script>
@endpush

@extends('layouts.app')
@section('title', 'Income & Expenditure Report')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .statement-sheet {
            background: #F6F5F1;
            border: 1px solid #E4E1D8;
            padding: 3rem 3.25rem;
            max-width: 920px;
            margin: 0 auto;
            color: #1C2430;
            font-family: 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        .statement-letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #122744;
            padding-bottom: 1.25rem;
            margin-bottom: 1.75rem;
        }
        .statement-letterhead h1 {
            font-family: 'Source Serif 4', serif;
            font-weight: 600;
            font-size: 1.65rem;
            color: #122744;
            margin: 0 0 0.2rem;
        }
        .statement-letterhead .school-name {
            font-size: 0.8rem;
            letter-spacing: 0.02em;
            color: #7A8391;
        }
        .statement-letterhead .period {
            text-align: right;
            font-size: 0.85rem;
            color: #4B5563;
        }
        .statement-letterhead .period strong {
            display: block;
            font-size: 1rem;
            color: #122744;
        }

        .statement-filter {
            display: flex;
            align-items: flex-end;
            gap: 1.75rem;
            margin-bottom: 2.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #E4E1D8;
        }
        .statement-filter .field label {
            display: block;
            font-size: 0.72rem;
            color: #7A8391;
            margin-bottom: 0.25rem;
        }
        .statement-filter input,
        .statement-filter select {
            border: none;
            border-bottom: 1px solid #C9C5B8;
            background: transparent;
            border-radius: 0;
            padding: 0.15rem 0;
            font-size: 0.95rem;
            color: #1C2430;
            min-width: 140px;
        }
        .statement-filter input:focus,
        .statement-filter select:focus {
            outline: none;
            border-bottom-color: #0F5C4A;
            box-shadow: none;
        }
        .statement-filter button {
            background: #122744;
            border: none;
            color: #fff;
            font-size: 0.85rem;
            padding: 0.45rem 1.1rem;
        }
        .statement-filter button:hover { background: #0F5C4A; }
        .statement-export {
            margin-left: auto;
            font-size: 0.8rem;
            color: #0F5C4A;
            text-decoration: none;
            border-bottom: 1px solid transparent;
        }
        .statement-export:hover { border-bottom-color: #0F5C4A; }

        .ledger-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 1.75rem;
        }
        @media (max-width: 720px) {
            .ledger-columns { grid-template-columns: 1fr; gap: 2rem; }
        }

        .ledger h2 {
            font-family: 'Source Serif 4', serif;
            font-weight: 600;
            font-size: 1rem;
            color: #122744;
            margin-bottom: 0.85rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #122744;
        }
        .ledger table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .ledger td { padding: 0.5rem 0; border-bottom: 1px solid #EAE7DD; }
        .ledger td:first-child { color: #3A4351; }
        .ledger td:last-child { text-align: right; color: #1C2430; }
        .ledger tr.empty td {
            text-align: center;
            color: #9AA1AB;
            font-style: italic;
            border-bottom: none;
            padding: 1rem 0;
        }
        .ledger tr.total td {
            border-top: 2px solid #122744;
            border-bottom: 4px double #122744;
            font-weight: 600;
            padding-top: 0.65rem;
        }

        .net-band {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 1.1rem 1.5rem;
            background: #122744;
            color: #F6F5F1;
        }
        .net-band.is-deficit { background: #3B1414; }
        .net-band .label {
            font-family: 'Source Serif 4', serif;
            font-size: 1.05rem;
        }
        .net-band .value { font-size: 1.4rem; font-weight: 600; }
        .net-band.is-deficit .value::before,
        .net-band.is-deficit .value::after { content: none; }

        .statement-footnote {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #9AA1AB;
        }
    </style>
@endpush

@section('content')
    <div class="statement-sheet">
        <div class="statement-letterhead">
            <div>
                <div class="school-name">{{ App\Models\Setting::allSettings()['school_name'] ?? config('app.name') }}</div>
                <h1>Income &amp; Expenditure Statement</h1>
            </div>
            <div class="period">
                Period
                <strong>{{ $term ? "Term {$term}, {$academicYear}" : "Full Year {$academicYear}" }}</strong>
            </div>
        </div>

        <form method="GET" class="statement-filter">
            <div class="field">
                <label>Academic Year</label>
                <input type="text" name="academic_year" value="{{ $academicYear }}">
            </div>
            <div class="field">
                <label>Term</label>
                <select name="term">
                    <option value="">Full Year</option>
                    @foreach([1,2,3] as $t)
                        <option value="{{ $t }}" @selected($term == $t)>Term {{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit">Filter</button>
            <a href="{{ route('accounting.reports.export', request()->query()) }}" class="statement-export">
                Export as Excel →
            </a>
        </form>

        <div class="ledger-columns">
            <div class="ledger">
                <h2>Income</h2>
                <table>
                    @forelse($income as $line)
                        <tr><td>{{ $line->name }}</td><td>{{ number_format($line->total, 2) }}</td></tr>
                    @empty
                        <tr class="empty"><td colspan="2">No income recorded for this period</td></tr>
                    @endforelse
                    <tr class="total"><td>Total Income</td><td>{{ number_format($incomeTotal, 2) }}</td></tr>
                </table>
            </div>

            <div class="ledger">
                <h2>Expenditure</h2>
                <table>
                    @forelse($expenses as $line)
                        <tr><td>{{ $line->name }}</td><td>{{ number_format($line->total, 2) }}</td></tr>
                    @empty
                        <tr class="empty"><td colspan="2">No expenditure recorded for this period</td></tr>
                    @endforelse
                    <tr class="total"><td>Total Expenditure</td><td>{{ number_format($expenseTotal, 2) }}</td></tr>
                </table>
            </div>
        </div>

        <div class="net-band {{ $net < 0 ? 'is-deficit' : '' }}">
            <span class="label">Net {{ $net >= 0 ? 'Surplus' : 'Deficit' }}</span>
            <span class="value">
                {{ $net < 0 ? '(' . number_format(abs($net), 2) . ')' : number_format($net, 2) }}
            </span>
        </div>

        <div class="statement-footnote">
            Generated {{ now()->format('d M Y, H:i') }} — figures in KES.
        </div>
    </div>
@endsection

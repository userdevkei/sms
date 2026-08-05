<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ $logoPath }}">
    <title>Export Students </title>

    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 14px; }
        .header h1 { font-size: 16px; margin: 0 0 2px; color: #0B3D62; }
        .header p { margin: 2px 0; color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0B3D62; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) td { background: #f9fafb; }
        .badge {
            padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold;
            color: #fff; background: #6b7280; text-transform: capitalize;
        }
        .footer { margin-top: 10px; text-align: right; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="header">
    <div class="logo"><img src="{{ $logoPath }}" style="max-height: 60px;"></div>
    <h1>{{ $schoolName }}</h1>
    <p>Generated: {{ $generatedAt }} &nbsp;|&nbsp; Total records: {{ $students->count() }}</p>
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>User ID</th>
        <th>Full Name</th>
        <th>Gender</th>
        <th>Email</th>
        <th>Phone</th>
        <th>County</th>
        <th>Sub County</th>
        <th>Ward</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    @forelse($students as $index => $user)
        @php
            $location = collect([$user->ward, $user->sub_county, $user->county])->filter()->implode(', ') ?: '—';
        @endphp
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $user->userID ?: '—' }}</td>
            <td>{{ $user->full_name }}</td>
            <td>{{ $user->gender ? ucfirst($user->gender) : '—' }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone_number ?: '—' }}</td>
            <td>{{ $user->county }}</td>
            <td>{{ $user->sub_county }}</td>
            <td>{{ $user->ward }}</td>
            <td>{{ $user->status }}</td>
        </tr>
    @empty
        <tr><td colspan="10" style="text-align:center; padding: 14px;">No students match the current filters.</td></tr>
    @endforelse
    </tbody>
</table>

</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report: {{ $reportType }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>{{ ucwords(str_replace('-', ' ', $reportType)) }} Report</h1>
    <p>Generated: {{ now()->format('Y-m-d H:i:s') }}</p>

    @if(isset($data['raw']))
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['raw'] as $row)
                    <tr>
                        <td>{{ $row['id'] ?? '-' }}</td>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td>{{ $row['date'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>

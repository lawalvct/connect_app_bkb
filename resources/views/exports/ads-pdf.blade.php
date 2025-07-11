<!DOCTYPE html>
<html>
<head>
    <title>Advertisements Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .status-active { color: green; }
        .status-paused { color: orange; }
        .status-stopped { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Advertisements Export Report</h2>
        <p>Generated on: {{ $export_date }}</p>
        <p>User: {{ $user->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Name</th>
                <th>Type</th>
                <th>Status</th>
                <th>Budget</th>
                <th>Spent</th>
                <th>Impressions</th>
                <th>Clicks</th>
                <th>CTR</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ads as $ad)
            <tr>
                <td>{{ $ad->id }}</td>
                <td>{{ $ad->ad_name }}</td>
                <td>{{ ucfirst($ad->type) }}</td>
                <td class="status-{{ $ad->status }}">{{ ucfirst($ad->status) }}</td>
                <td>${{ number_format($ad->budget, 2) }}</td>
                <td>${{ number_format($ad->total_spent, 2) }}</td>
                <td>{{ number_format($ad->current_impressions) }}</td>
                <td>{{ number_format($ad->clicks) }}</td>
                <td>{{ $ad->ctr }}%</td>
                <td>{{ $ad->start_date->format('Y-m-d') }}</td>
                <td>{{ $ad->end_date->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

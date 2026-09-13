@extends('admin.layout')

@section('title', 'Subscriptions')

@section('content')
    <h2>Subscriptions</h2>
    <p class="muted">Demonstration / mock subscription data only. No payment gateway is connected and no real charges are processed.</p>

    <div class="cards">
        <div class="card">
            <div class="label">Total Subscribers</div>
            <div class="value">{{ $summary['total'] }}</div>
        </div>
        <div class="card">
            <div class="label">Active Subscriptions</div>
            <div class="value">{{ $summary['active'] }}</div>
        </div>
        <div class="card">
            <div class="label">Expiring Soon</div>
            <div class="value">{{ $summary['expiring'] }}</div>
        </div>
        <div class="card">
            <div class="label">Demo Revenue</div>
            <div class="value">₹{{ number_format($summary['revenue']) }}</div>
        </div>
    </div>

    <div class="panel">
        <h2>Dummy subscription records</h2>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Start date</th>
                    <th>Valid until</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subscriptions as $row)
                    <tr>
                        <td>{{ $row['user'] }}</td>
                        <td>{{ $row['plan'] }}</td>
                        <td>₹{{ number_format($row['amount']) }}</td>
                        <td>{{ $row['start_date'] }}</td>
                        <td>{{ $row['renews_on'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td><a href="{{ route('admin.subscriptions.show', $row['id']) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

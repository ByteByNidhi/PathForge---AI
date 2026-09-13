@extends('admin.layout')

@section('title', 'Subscription details')

@section('content')
    <h2>Subscription Details</h2>
    <p class="muted">Demonstration record. Payment method is not connected.</p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.subscriptions.index') }}">Back</a>
        <form method="POST" action="{{ route('admin.subscriptions.upgrade', $subscription['id']) }}" class="inline-form">
            @csrf
            <button class="btn" type="submit">Upgrade Plan</button>
        </form>
    </div>

    <dl class="pf-meta">
        <dt>User</dt>
        <dd>{{ $subscription['user'] }} ({{ $subscription['email'] }})</dd>
        <dt>Plan</dt>
        <dd>{{ $subscription['plan'] }}</dd>
        <dt>Status</dt>
        <dd>{{ $subscription['status'] }}</dd>
        <dt>Start Date</dt>
        <dd>{{ $subscription['start_date'] }}</dd>
        <dt>Next Renewal</dt>
        <dd>{{ $subscription['renews_on'] }}</dd>
        <dt>Amount</dt>
        <dd>₹{{ number_format($subscription['amount']) }}</dd>
        <dt>Payment Method</dt>
        <dd>{{ $subscription['payment_method'] }}</dd>
    </dl>

    <div class="panel">
        <h2>Payment History</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subscription['history'] as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>₹{{ number_format($row['amount']) }}</td>
                        <td>{{ $row['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

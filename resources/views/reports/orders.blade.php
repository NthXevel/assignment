@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Orders Report</h1>
    <p>Total Orders: {{ $stats['total_orders'] }}</p>
    <p>Total Value: RM {{ number_format($stats['total_value'], 2) }}</p>
    <p>Pending Orders: {{ $stats['pending_orders'] }}</p>
    <p>Completed Orders: {{ $stats['completed_orders'] }}</p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Requesting Branch</th>
                <th>Supplying Branch</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->requestingBranch->name }}</td>
                <td>{{ $order->supplyingBranch->name }}</td>
                <td>{{ ucfirst($order->status) }}</td>
                <td>RM {{ number_format($order->total_amount, 2) }}</td>
                <td>{{ $order->created_at->format('d-m-Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="settings-page">
    <div class="settings-container">
        <h1>Order Details - {{ $order->order_number }}</h1>
        <p>Status: <strong>{{ ucfirst($order->status) }}</strong></p>

        <div class="settings-card">
            <p><strong>Created By:</strong> {{ $order->creator->name }}</p>
            <p><strong>Requesting Branch:</strong> {{ $order->requestingBranch->name }}</p>
            <p><strong>Supplying Branch:</strong> {{ $order->supplyingBranch->name }}</p>
            <p><strong>Notes:</strong> {{ $order->notes ?? '-' }}</p>

            <h3>Items</h3>
            <table class="form-control">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Page Title -->
    <div class="page-title mb-4">
        <h1>Orders</h1>
        <p class="page-subtitle">Manage all branch transfer orders here.</p>
    </div>

    <!-- Create Order Button -->
    <div class="mb-4">
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Order
        </a>
    </div>

    <!-- Orders Table -->
    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-list"></i> Orders List</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Requesting Branch</th>
                        <th>Supplying Branch</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><strong>{{ $order->order_number }}</strong></td>
                            <td>{{ $order->requestingBranch->name ?? '-' }}</td>
                            <td>{{ $order->supplyingBranch->name ?? '-' }}</td>
                            <td>
                                <span class="status-badge status-{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>RM {{ number_format($order->total_amount, 2) }}</td>
                            <td>{{ $order->creator->username ?? '-' }}</td>
                            <td>{{ $order->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">View</a>
                                
                                @if($order->status == 'pending')
                                    <form action="{{ route('orders.approve', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                @endif

                                @if($order->status == 'approved')
                                    <form action="{{ route('orders.ship', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Ship</button>
                                    </form>
                                @endif

                                @if($order->status == 'shipped')
                                    <form action="{{ route('orders.receive', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Receive</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No orders found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .page-title h1 {
        font-size: 1.75rem;
        font-weight: bold;
    }
    .page-subtitle {
        color: #6b7280;
    }
    .table-container {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
    }
    .chart-title {
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
    }
    .status-pending { background-color: #f59e0b; }
    .status-approved { background-color: #10b981; }
    .status-shipped { background-color: #3b82f6; }
    .status-received { background-color: #6b7280; }
    .btn {
        border-radius: 0.375rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.85rem;
    }
    .btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
</style>
@endpush

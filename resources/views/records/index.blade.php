@extends('layouts.app')

@section('content')
    <div class="records-page">
        <div class="records-container">
            <h1><i class="fas fa-history"></i> Records Management</h1>
            <p>View stock movement and order history</p>

            <div class="records-grid">
                <!-- Stock Movements -->
                <div class="records-card">
                    <h2><i class="fas fa-box"></i> Stock Movements</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Reason</th>
                                    <th>Change</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockMovements as $movement)
                                    <tr>
                                        <td>#{{ $movement->id }}</td>
                                        <td>{{ $movement->stock->product->name ?? '-' }}</td>
                                        <td>{{ ucfirst($movement->reason) }}</td>
                                        <td>{{ $movement->quantity_change }}</td>
                                        <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No stock movement records</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders -->
                <div class="records-card">
                    <h2><i class="fas fa-shopping-cart"></i> Orders (Shipped & Canceled)</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>From Branch</th>
                                    <th>To Branch</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>#{{ $order->id }}</td>
                                        <td>{{ $order->supplyingBranch->name ?? '-' }}</td>
                                        <td>{{ $order->requestingBranch->name ?? '-' }}</td>
                                        <td>
                                            <span class="{{ $order->status == 'shipped' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No shipped or canceled orders</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .records-page {
            display: flex;
            justify-content: center;
            padding: 30px;
        }

        .records-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
        }

        .records-container h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #667eea;
        }

        .records-container p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
        }

        .records-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .records-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .records-card h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .text-green-600 {
            color: #16a34a;
            font-weight: bold;
        }

        .text-red-600 {
            color: #dc2626;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .records-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

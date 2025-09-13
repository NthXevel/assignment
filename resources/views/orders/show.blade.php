@extends('layouts.app')

@section('content')
    <div class="settings-page">
        <div class="settings-container">
            <h1><i class="fas fa-receipt"></i> Order Details</h1>
            <p>View order #{{ $order->order_number }}</p>

            <div class="settings-grid">
                {{-- Order Information --}}
                <div class="settings-card">
                    <h2><i class="fas fa-info-circle"></i> Order Information</h2>

                    <p><strong>Status:</strong>
                        <span style="padding:6px 10px; border-radius:12px; font-weight:600; font-size:0.85rem;
                                @php
                                    $statusStyles = [
                                        'pending' => 'background:#fff7ed;color:#92400e',
                                        'approved' => 'background:#ecfdf5;color:#065f46',
                                        'shipped' => 'background:#eff6ff;color:#1e3a8a',
                                        'received' => 'background:#f3f4f6;color:#374151',
                                        'cancelled' => 'background:#fff1f2;color:#991b1b',
                                    ];
                                @endphp
                                {{ $statusStyles[$order->status] ?? '' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </p>
                    <p><strong>Priority:</strong>
                        @php
                            $priorityStyles = [
                                'urgent' => 'background:#fee2e2;color:#991b1b',
                                'standard' => 'background:#e0e7ff;color:#3730a3',
                            ];
                        @endphp
                        <span style="padding:6px 10px; border-radius:12px; font-weight:600; font-size:0.85rem; {{ $priorityStyles[$order->priority] ?? '' }}">
                            {{ ucfirst($order->priority ?? 'standard') }}
                        </span>
                    </p>
                    <p><strong>Created By:</strong> {{ $order->creator->name ?? $order->creator->username }}</p>
                    <p><strong>Requesting Branch:</strong> {{ $order->requestingBranch->name }}</p>
                    <p><strong>Supplying Branch:</strong> {{ $order->supplyingBranch->name }}</p>
                    <p><strong>Notes:</strong> {{ $order->notes ?? '-' }}</p>
                    <p><strong>Created At:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</p>
                </div>

                {{-- Order Items --}}
                <div class="settings-card">
                    <h2><i class="fas fa-boxes"></i> Order Items</h2>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price (RM)</th>
                                <th>Total (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->unit_price, 2) }}</td>
                                    <td>{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr style="font-weight: bold; background: #f9fafb;">
                                <td colspan="3" class="text-right">Grand Total</td>
                                <td>RM {{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Back Button --}}
            <div class="form-actions mt-4">
                <a href="{{ route('orders.index') }}" class="btn-theme btn-theme-danger">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <style>
        .table th,
        .table td {
            padding: 10px 12px;
            text-align: left;
        }

        .settings-page {
            display: flex;
            justify-content: center;
            padding: 30px;
        }

        .settings-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 900px;
        }

        .settings-container h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #667eea;
        }

        .settings-container p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .settings-card h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
        }

        .form-group p,
        .form-group ul {
            background: #f7f7f7;
            padding: 10px;
            border-radius: 6px;
            margin: 0;
        }

        .form-group ul {
            list-style: disc inside;
        }

        .btn-theme {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-theme-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-theme-secondary {
            background: linear-gradient(135deg, #6B7280, #4B5563);
            color: white;
        }

        .btn-theme-secondary:hover {
            background: linear-gradient(135deg, #4B5563, #6B7280);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(75, 85, 99, 0.3);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-start;
            align-items: center;
        }
    </style>
@endsection
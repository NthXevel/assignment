@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Stock Report</h1>
    <p>Total Stock Value: RM {{ number_format($totalValue, 2) }}</p>
    <p>Low Stock Items: {{ $lowStockCount }}</p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Branch</th>
                <th>Quantity</th>
                <th>Minimum Threshold</th>
                <th>Cost Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stocks as $stock)
            <tr>
                <td>{{ $stock->product->name }}</td>
                <td>{{ $stock->product->category->name }}</td>
                <td>{{ $stock->branch->name }}</td>
                <td>{{ $stock->quantity }}</td>
                <td>{{ $stock->minimum_threshold }}</td>
                <td>RM {{ number_format($stock->product->cost_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

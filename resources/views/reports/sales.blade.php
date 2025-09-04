@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Sales Report</h1>
    <p>Total Transfers: {{ $analytics['total_transfers'] }}</p>
    <p>Total Value: RM {{ number_format($analytics['total_value'], 2) }}</p>
    <p>Average Order Value: RM {{ number_format($analytics['average_order_value'], 2) }}</p>

    <h3>Top Products</h3>
    <ul>
        @foreach($analytics['top_products'] as $product => $qty)
            <li>{{ $product }}: {{ $qty }} units</li>
        @endforeach
    </ul>

    <h3>Branch Performance</h3>
    <ul>
        @foreach($analytics['branch_performance'] as $branch => $value)
            <li>{{ $branch }}: RM {{ number_format($value, 2) }}</li>
        @endforeach
    </ul>
</div>
@endsection

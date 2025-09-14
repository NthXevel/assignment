<!-- Author: Lee Kai Yi -->
@extends('layouts.app')

@section('content')
<div class="records-page">
  <div class="records-container">
    <h1><i class="fas fa-history"></i> Records Management</h1>
    <p>View stock movement and order history</p>

    <div class="records-grid">
      {{-- Stock Movements --}}
      <div class="records-card">
        <h2><i class="fas fa-box"></i> Stock Movements</h2>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Reason</th>
                <th>Change</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($stockMovements as $m)
                @php
                  $dt = \Illuminate\Support\Carbon::parse($m->created_at ?? now());
                @endphp
                <tr>
                  <td>#{{ $m->id }}</td>
                  <td>{{ ucfirst($m->reason ?? '-') }}</td>
                  <td>{{ $m->quantity_change ?? 0 }}</td>
                  <td>{{ $dt->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center">No stock movement records</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Movements pagination (uses custom pageName = stocks_page) --}}
        <div class="mt-3">
          {{ $stockMovements->onEachSide(1)->links() }}
        </div>
      </div>

      {{-- Orders --}}
      <div class="records-card">
        <h2><i class="fas fa-shopping-cart"></i> Orders (Received / Canceled)</h2>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>From Branch</th>
                <th>To Branch</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $o)
                @php
                  $dt = \Illuminate\Support\Carbon::parse($o->created_at ?? now());
                  $from = $branchNameMap[$o->supplying_branch_id]   ?? ('#'.$o->supplying_branch_id);
                  $to   = $branchNameMap[$o->requesting_branch_id]  ?? ('#'.$o->requesting_branch_id);
                  $cls  = $o->status === 'received' ? 'text-green-600' : ($o->status === 'canceled' ? 'text-red-600' : 'text-gray-700');
                @endphp
                <tr>
                  <td>#{{ $o->order_number ?? $o->id }}</td>
                  <td>{{ $from }}</td>
                  <td>{{ $to }}</td>
                  <td><span class="{{ $cls }}">{{ ucfirst($o->status) }}</span></td>
                  <td>{{ $dt->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center">No received or canceled orders</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Orders pagination (uses custom pageName = orders_page) --}}
        <div class="mt-3">
          {{ $orders->onEachSide(1)->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .records-page { display:flex; justify-content:center; padding:30px; }
  .records-container { background:rgba(255,255,255,.95); padding:30px; border-radius:15px; box-shadow:0 8px 32px rgba(0,0,0,.15); width:100%; }
  .records-container h1 { font-size:1.8rem; font-weight:700; margin-bottom:10px; color:#667eea; }
  .records-container p { font-size:.9rem; color:#666; margin-bottom:25px; }
  .records-grid { display:grid; grid-template-columns:1fr 1fr; gap:25px; }
  .records-card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.1); }
  .records-card h2 { font-size:1.2rem; font-weight:600; margin-bottom:15px; color:#333; }
  .table { width:100%; border-collapse:collapse; }
  .table th,.table td { padding:10px; border-bottom:1px solid #eee; }
  .text-green-600 { color:#16a34a; font-weight:700; }
  .text-red-600 { color:#dc2626; font-weight:700; }
  .text-gray-700 { color:#374151; font-weight:700; }
  .records-container nav svg {
  width: 16px !important;
  height: 16px !important;
}
.records-container nav a,
.records-container .pagination .page-link {
  text-decoration: none !important;
  box-shadow: none;         
}

.records-container nav a:hover,
.records-container .pagination .page-link:hover {
  text-decoration: none !important;
}
.records-container .pagination .page-item.active .page-link {
  font-weight: 700;
}

.records-container .pagination .page-item.disabled .page-link {
  opacity: .5;
  pointer-events: none;
}
  @media (max-width:768px){ .records-grid{ grid-template-columns:1fr; } }
</style>
@endsection

@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
            <h2 style="margin:0;">Order Penjualan</h2>
            <a class="btn" href="{{ route('orders.create') }}">+ Order</a>
        </div>

        <form method="get" class="row" style="margin-bottom:12px;">
            <select name="status">
                <option value="">Semua Status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ strtoupper($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn secondary">Filter</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No Order</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Sales</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>{{ $order->customer->name ?? '-' }}</td>
                            <td>{{ $order->salesUser->name ?? '-' }}</td>
                            <td><span class="badge {{ $order->status === 'rejected' ? 'danger' : ($order->status === 'approved' || $order->status === 'fulfilled' ? 'success' : 'info') }}">{{ strtoupper($order->status) }}</span></td>
                            <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn secondary" href="{{ route('orders.edit', $order) }}">Edit</a>
                                    <form method="post" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Hapus order ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Belum ada order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $orders->links() }}</div>
    </section>
@endsection

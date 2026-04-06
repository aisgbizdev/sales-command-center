@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
            <h2 style="margin:0;">Data Pelanggan</h2>
            <a class="btn" href="{{ route('customers.create') }}">+ Pelanggan</a>
        </div>

        <form method="get" class="row" style="margin-bottom:12px;">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama / kode / telepon">
            <button type="submit" class="btn secondary">Cari</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->customer_code }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone ?: '-' }}<br>{{ $customer->email ?: '-' }}</td>
                            <td><span class="badge {{ $customer->status === 'active' ? 'success' : 'warn' }}">{{ strtoupper($customer->status) }}</span></td>
                            <td>{{ $customer->creator->name ?? '-' }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edit</a>
                                    <form method="post" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Hapus pelanggan ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Belum ada data pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $customers->links() }}</div>
    </section>
@endsection

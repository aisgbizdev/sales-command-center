@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
            <h2 style="margin:0;">Aktivitas Penjualan</h2>
            <a class="btn" href="{{ route('activities.create') }}">+ Aktivitas</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Ringkasan</th>
                        <th>Pelanggan</th>
                        <th>Sales</th>
                        <th>Follow Up</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity->activity_date->format('d M Y') }}</td>
                            <td><span class="badge info">{{ strtoupper($activity->activity_type) }}</span></td>
                            <td>
                                <strong>{{ $activity->summary }}</strong>
                                @if($activity->outcome)
                                    <br><span style="color:#5e6a7d;">{{ $activity->outcome }}</span>
                                @endif
                            </td>
                            <td>{{ $activity->customer->name ?? '-' }}</td>
                            <td>{{ $activity->salesUser->name ?? '-' }}</td>
                            <td>{{ $activity->next_follow_up_date ? $activity->next_follow_up_date->format('d M Y') : '-' }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <a class="btn secondary" href="{{ route('activities.edit', $activity) }}">Edit</a>
                                    <form method="post" action="{{ route('activities.destroy', $activity) }}" onsubmit="return confirm('Hapus aktivitas ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px;">{{ $activities->links() }}</div>
    </section>
@endsection

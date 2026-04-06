@extends('layouts.app')

@section('content')
@php
    $statusBadgeClass = static fn (string $status): string => match ($status) {
        'baru' => 'status-baru',
        'dihubungi' => 'status-dihubungi',
        'dibalas' => 'status-dibalas',
        'sedang_berjalan' => 'status-sedang-berjalan',
        'tindak_lanjut' => 'status-tindak-lanjut',
        'penutupan' => 'status-penutupan',
        'hilang' => 'status-hilang',
        default => 'info',
    };
@endphp
<section class="card">
    <div class="hero-panel">
        <div class="hero-copy">
            <h2 class="page-title">Manajemen Prospek</h2>
            <p class="page-subtitle">Ini adalah daftar kerja harian utama. Cari prospek, cek follow up, lalu buka detail jika perlu pembaruan lebih lengkap.</p>
            <div class="info-strip">
                <span class="badge info">Cari nama atau nomor HP</span>
                <span class="badge warn">Pantau follow up terlambat</span>
                <span class="badge success">Buka detail untuk update lengkap</span>
            </div>
        </div>
        <div class="mini-guide">
            <h3>Flow paling mudah</h3>
            <p><strong>1.</strong> Gunakan filter di bawah untuk menyempitkan daftar.</p>
            <p><strong>2.</strong> Klik nama prospek untuk melihat riwayat dan detail.</p>
            @if($canCreateProspect)
                <div class="hero-actions" style="margin-top:12px;">
                    <a class="btn" href="{{ route('prospects.create') }}">+ Tambah Prospek Baru</a>
                </div>
            @endif
        </div>
    </div>

    <form method="get" class="filters">
        <input name="q" value="{{ request('q') }}" placeholder="Cari nama, perusahaan, kode, atau nomor HP">
        <select name="account_category">
            <option value="">Semua Kategori Akun</option>
            @foreach($accountCategories as $accountCategory)
                <option value="{{ $accountCategory }}" @selected(request('account_category') === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Semua Status</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? strtoupper($status) }}</option>
            @endforeach
        </select>
        <select name="owner_id">
            <option value="">Semua Owner</option>
            @foreach($salesUsers as $sales)
                <option value="{{ $sales->id }}" @selected((string) request('owner_id') === (string) $sales->id)>{{ $sales->name }}</option>
            @endforeach
        </select>
        <select name="follow_up">
            <option value="">Semua Follow Up</option>
            <option value="overdue" @selected(request('follow_up') === 'overdue')>Terlambat</option>
            <option value="today" @selected(request('follow_up') === 'today')>Hari Ini</option>
            <option value="week" @selected(request('follow_up') === 'week')>7 Hari</option>
        </select>
        <button type="submit" class="btn secondary">Filter</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Perusahaan</th>
                    <th>Kategori Akun</th>
                    <th>Owner</th>
                    <th>Team/Unit</th>
                    <th>Status</th>
                    <th>Follow Up</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prospects as $prospect)
                    <tr>
                        <td>{{ $prospect->prospect_code }}</td>
                        <td><a href="{{ route('prospects.show', $prospect) }}"><strong>{{ $prospect->name }}</strong></a></td>
                        <td>{{ $prospect->company ?: '-' }}</td>
                        <td><span class="badge info">{{ $accountCategoryLabels[$prospect->account_category] ?? strtoupper($prospect->account_category) }}</span></td>
                        <td>{{ $prospect->owner->name ?? '-' }}</td>
                        <td>{{ $prospect->team->name ?? '-' }} / {{ $prospect->unit->name ?? '-' }}</td>
                        <td><span class="badge {{ $statusBadgeClass($prospect->status) }}">{{ $statusLabels[$prospect->status] ?? strtoupper($prospect->status) }}</span></td>
                        <td>
                            {{ $prospect->next_follow_up_date?->format('d M Y') ?: '-' }}
                            @if($prospect->next_follow_up_date && $prospect->next_follow_up_date->isPast() && $prospect->status !== 'hilang')
                                <br><span class="badge danger">Terlambat</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                @if(auth()->user()->canEditProspect($prospect))
                                    <a class="btn secondary" href="{{ route('prospects.edit', $prospect) }}">Edit</a>
                                @endif
                                @if(auth()->user()->canDeleteProspect($prospect))
                                    <form method="post" action="{{ route('prospects.destroy', $prospect) }}" onsubmit="return confirm('Hapus prospek ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn danger">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <strong>Belum ada prospek yang cocok dengan filter ini.</strong>
                                Ubah filter pencarian atau tambahkan prospek baru agar daftar mulai terisi.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:10px;">{{ $prospects->links() }}</div>
</section>
@endsection

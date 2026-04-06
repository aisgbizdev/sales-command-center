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
            <h2 class="page-title">Kinerja Penjualan</h2>
            <p class="page-subtitle">Halaman ini membantu user awam membaca siapa yang aktif, siapa yang banyak closing, dan siapa yang perlu perhatian karena follow up terlambat.</p>
        </div>
        <div class="mini-guide">
            <h3>Cara baca paling cepat</h3>
            <p><strong>Total prospek:</strong> beban kerja.</p>
            <p><strong>Penutupan:</strong> hasil akhir.</p>
            <p><strong>Terlambat:</strong> titik risiko yang perlu dibenahi.</p>
        </div>
    </div>

    <form method="get" class="filters compact">
        <label>Dari Tanggal
            <input type="date" name="from" value="{{ request('from', $from->format('Y-m-d')) }}">
        </label>
        <label>Sampai Tanggal
            <input type="date" name="to" value="{{ request('to', $to->format('Y-m-d')) }}">
        </label>
        <label>Kategori Akun
            <select name="account_category">
                <option value="">Semua</option>
                @foreach($accountCategories as $accountCategory)
                    <option value="{{ $accountCategory }}" @selected($selectedAccountCategory === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn secondary">Terapkan</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Sales</th>
                    <th>Total Prospek</th>
                    <th>Penutupan</th>
                    <th>Hilang</th>
                    <th>Aktivitas</th>
                    <th>Terlambat</th>
                    <th>Estimasi Nilai</th>
                    <th>Rasio Penutupan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesUsers as $sales)
                    @php
                        $ratio = $sales->total_prospects > 0 ? round(($sales->closing_count / $sales->total_prospects) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $sales->name }}</td>
                        <td>{{ $sales->total_prospects }}</td>
                        <td>{{ $sales->closing_count }}</td>
                        <td>{{ $sales->lost_count }}</td>
                        <td>{{ $sales->activity_count }}</td>
                        <td>{{ $sales->overdue_count }}</td>
                        <td>Rp {{ number_format($sales->total_value ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $ratio }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <strong>Belum ada data kinerja pada periode ini.</strong>
                                Coba ubah rentang tanggal atau mulai input prospek dan aktivitas harian terlebih dahulu.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h3 style="margin-top:0;">Distribusi Status Prospek</h3>
    <div class="row">
        @foreach($statusLabels as $status => $label)
            <div class="kpi-box">
                <h4>{{ $label }}</h4>
                <strong>{{ $statusBreakdown[$status] ?? 0 }}</strong>
            </div>
        @endforeach
    </div>
</section>

<section class="card">
    <h3 style="margin-top:0;">Deteksi Prospek Terlambat</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Prospek</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Tgl Follow Up</th>
                </tr>
            </thead>
            <tbody>
                @forelse($overdueProspects as $prospect)
                    <tr>
                        <td>{{ $prospect->prospect_code }}</td>
                        <td><a href="{{ route('prospects.show', $prospect) }}">{{ $prospect->name }}</a></td>
                        <td>{{ $prospect->owner->name ?? '-' }}</td>
                        <td><span class="badge {{ $statusBadgeClass($prospect->status) }}">{{ $statusLabels[$prospect->status] ?? strtoupper($prospect->status) }}</span></td>
                        <td><span class="badge danger">{{ $prospect->next_follow_up_date?->format('d M Y') }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <strong>Tidak ada prospek yang terlambat follow up.</strong>
                                Ini berarti jadwal follow up pada periode ini masih terjaga.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

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

        $baseBars = [
            (int) ($statusSummary['baru'] ?? 0),
            (int) ($statusSummary['dihubungi'] ?? 0),
            (int) ($statusSummary['dibalas'] ?? 0),
            (int) ($statusSummary['sedang_berjalan'] ?? 0),
            (int) ($statusSummary['tindak_lanjut'] ?? 0),
            (int) ($statusSummary['penutupan'] ?? 0),
            (int) ($statusSummary['hilang'] ?? 0),
        ];

        $bars = [];
        for ($i = 0; $i < 12; $i++) {
            $bars[] = max(1, $baseBars[$i % count($baseBars)] + (($i % 3) * 2));
        }

        $maxBar = max($bars);
        $healthPercent = $totalProspects > 0 ? round(($wonProspects / $totalProspects) * 100, 2) : 0;
    @endphp

    <section class="card">
        <div class="hero-panel">
            <div class="hero-copy">
                <h2 class="page-title">Dashboard Harian</h2>
                <p class="page-subtitle">Gunakan halaman ini untuk melihat kondisi penjualan secara cepat: berapa prospek aktif, mana yang closing, dan mana yang perlu follow up hari ini.</p>
                <div class="hero-actions" style="margin-top:12px;">
                    @if(auth()->user()->canCreateProspect())
                        <a href="{{ route('prospects.create') }}" class="btn">+ Input Prospek Baru</a>
                    @endif
                    <a href="{{ route('prospects.pipeline') }}" class="btn secondary">Buka Pipeline Cepat</a>
                </div>
            </div>
            <div class="mini-guide">
                <h3>Lihat dulu 3 hal ini</h3>
                <p><strong>1.</strong> Cek jumlah prospek aktif.</p>
                <p><strong>2.</strong> Cek follow up yang overdue atau due today.</p>
                <p><strong>3.</strong> Buka pipeline untuk update status dengan cepat.</p>
                <form method="get" class="action-row" style="margin-top:12px;">
                    <select name="account_category">
                        <option value="">Semua Kategori Akun</option>
                        @foreach($accountCategories as $accountCategory)
                            <option value="{{ $accountCategory }}" @selected($selectedAccountCategory === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn secondary">Terapkan</button>
                </form>
            </div>
        </div>

        <div class="grid kpi" style="margin-top:10px;">
            <div class="kpi-box">
                <h4>Total Prospek</h4>
                <strong>{{ number_format($totalProspects) }}</strong>
                <div class="metric-note">Semua prospek yang terlihat sesuai role kamu.</div>
            </div>
            <div class="kpi-box">
                <h4>Prospek Aktif</h4>
                <strong>{{ number_format($openProspects) }}</strong>
                <div class="metric-note">Prospek yang masih berjalan dan belum dianggap hilang.</div>
            </div>
            <div class="kpi-box">
                <h4>Penutupan</h4>
                <strong>{{ number_format($wonProspects) }}</strong>
                <div class="metric-note">Jumlah prospek yang berhasil masuk tahap closing.</div>
            </div>
            <div class="kpi-box">
                <h4>Input Hari Ini</h4>
                <strong>{{ number_format($todayInputCount) }}</strong>
                <div class="metric-note">Aktivitas yang sudah dicatat hari ini.</div>
            </div>
        </div>
    </section>

    <section class="dashboard-split">
        <article class="card" style="margin:0;">
            <h3 style="margin-top:0;">Activity Overview</h3>
            <p class="page-subtitle" style="margin-top:2px;">Visual cepat pola pergerakan status.</p>

            <div class="dashboard-chart">
                @foreach($bars as $index => $bar)
                    <div class="dashboard-chart-col">
                        <div class="dashboard-chart-bar" style="height:{{ (int) round(($bar / $maxBar) * 180) }}px;"></div>
                        <small class="dashboard-chart-label">{{ $index + 1 }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="card" style="margin:0;">
            <h3 style="margin-top:0;">Monthly Target</h3>
            <p class="page-subtitle" style="margin-top:2px;">Rasio prospek penutupan.</p>

            <div class="dashboard-gauge">
                <div class="dashboard-gauge-fill" style="clip-path: inset(0 {{ max(0, 100 - $healthPercent) }}% 0 0);"></div>
            </div>

            <div class="dashboard-gauge-value">
                <strong>{{ number_format($healthPercent, 2) }}%</strong>
                <div><span class="badge {{ $healthPercent >= 50 ? 'success' : 'warn' }}">{{ $healthPercent >= 50 ? 'On Track' : 'Need Boost' }}</span></div>
            </div>

            <div class="summary-row" style="margin-top:14px;">
                <div class="kpi-box"><h4>Overdue</h4><strong>{{ $overdueCount }}</strong></div>
                <div class="kpi-box"><h4>Due Today</h4><strong>{{ $dueTodayCount }}</strong></div>
            </div>
        </article>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Status Snapshot</h3>
        <div class="row">
            @foreach(['baru', 'dihubungi', 'dibalas', 'sedang_berjalan', 'tindak_lanjut', 'penutupan', 'hilang'] as $status)
                <div class="kpi-box">
                    <h4>{{ $statusLabels[$status] ?? strtoupper($status) }}</h4>
                    <strong>{{ $statusSummary[$status] ?? 0 }}</strong>
                    <div style="margin-top:8px;"><span class="badge {{ $statusBadgeClass($status) }}">{{ $statusLabels[$status] ?? strtoupper($status) }}</span></div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Follow Up Terdekat</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Prospek</th>
                        <th>Owner</th>
                        <th>Tanggal Follow Up</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingFollowUp as $prospect)
                        <tr>
                            <td>{{ $prospect->prospect_code }}</td>
                            <td><a href="{{ route('prospects.show', $prospect) }}">{{ $prospect->name }}</a></td>
                            <td>{{ $prospect->owner->name ?? '-' }}</td>
                            <td>{{ $prospect->next_follow_up_date?->format('d M Y') ?: '-' }}</td>
                            <td><span class="badge {{ $statusBadgeClass($prospect->status) }}">{{ $statusLabels[$prospect->status] ?? strtoupper($prospect->status) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <strong>Belum ada jadwal follow up yang tercatat.</strong>
                                    Tambahkan tanggal follow up di prospek supaya monitoring harian jadi hidup.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

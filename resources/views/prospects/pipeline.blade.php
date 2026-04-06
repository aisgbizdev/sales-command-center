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
            <h2 class="page-title">Pipeline Board</h2>
            <p class="page-subtitle">Halaman ini dibuat untuk update cepat. Idealnya satu prospek bisa diperbarui dalam 15-30 detik langsung dari kartu.</p>
            <div class="info-strip">
                <span class="badge info">Pilih status baru</span>
                <span class="badge warn">Isi tanggal follow up</span>
                <span class="badge success">Simpan tanpa buka halaman lain</span>
            </div>
        </div>
        <div class="mini-guide">
            <h3>Prioritas yang perlu dicek</h3>
            <div class="info-strip">
                <span class="badge danger">Terlambat: {{ $overdueCount }}</span>
                <span class="badge warn">Hari Ini: {{ $dueTodayCount }}</span>
                <span class="badge info"><= 3 Hari: {{ $dueSoonCount }}</span>
            </div>
        </div>
    </div>

    <form method="get" class="filters compact">
        <input name="q" value="{{ request('q') }}" placeholder="Cari cepat nama / perusahaan / kode">
        <select name="account_category">
            <option value="">Semua Kategori Akun</option>
            @foreach($accountCategories as $accountCategory)
                <option value="{{ $accountCategory }}" @selected(request('account_category') === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
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
</section>

<section class="pipeline-board">
    @foreach($statuses as $status)
        <article class="pipeline-column">
            <header>
                <h3>{{ $statusLabels[$status] ?? strtoupper($status) }}</h3>
                <span>{{ $pipeline[$status]->count() }}</span>
            </header>

            <div class="pipeline-items">
                @forelse($pipeline[$status] as $prospect)
                    <div class="pipeline-card">
                        <div class="pipeline-card-head">
                            <strong>{{ $prospect->name }}</strong>
                            <small>{{ $prospect->prospect_code }}</small>
                        </div>
                        <div class="pipeline-meta" style="margin-top:4px;">
                            {{ $prospect->company ?: '-' }}<br>
                            Owner: {{ $prospect->owner->name ?? '-' }}<br>
                            Akun: {{ $accountCategoryLabels[$prospect->account_category] ?? strtoupper($prospect->account_category) }}
                        </div>

                        <div style="margin-top:8px; font-size:12px;" class="stack">
                            <span class="badge {{ $statusBadgeClass($prospect->status) }}">{{ $statusLabels[$prospect->status] ?? strtoupper($prospect->status) }}</span>
                            Follow Up: {{ $prospect->next_follow_up_date?->format('d M Y') ?: '-' }}
                            @if($prospect->next_follow_up_date && $prospect->next_follow_up_date->isPast() && $prospect->status !== 'hilang')
                                <span class="badge danger">Terlambat</span>
                            @endif
                        </div>

                        <form method="post" action="{{ route('prospects.quick-update', $prospect) }}" class="quick-update-form">
                            @csrf
                            @method('patch')
                            <select name="status" required>
                                @foreach($statuses as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($prospect->status === $statusOption)>{{ $statusLabels[$statusOption] ?? strtoupper($statusOption) }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="next_follow_up_date" value="{{ $prospect->next_follow_up_date?->format('Y-m-d') }}">
                            <input type="text" name="quick_note" placeholder="Apa hasil singkat update ini? (opsional)">
                            <select name="user_temperature">
                                <option value="">User temperature</option>
                                @foreach($userTemperatures as $temperature)
                                    <option value="{{ $temperature }}" @selected($prospect->user_temperature === $temperature)>{{ $userTemperatureLabels[$temperature] ?? strtoupper($temperature) }}</option>
                                @endforeach
                            </select>
                            <select name="dominant_emotion">
                                <option value="">Emosi dominan</option>
                                @foreach($dominantEmotions as $emotion)
                                    <option value="{{ $emotion }}" @selected($prospect->dominant_emotion === $emotion)>{{ $dominantEmotionLabels[$emotion] ?? strtoupper($emotion) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="main_objection" value="{{ $prospect->main_objection }}" placeholder="Keberatan utama">
                            <label class="checkbox-inline">Bridge candidate
                                <input type="checkbox" name="bridge_candidate" value="1" @checked($prospect->bridge_candidate)>
                            </label>
                            <button type="submit" class="btn">Simpan Cepat</button>
                        </form>

                        <a href="{{ route('prospects.show', $prospect) }}" class="pipeline-link">Detail lengkap</a>
                    </div>
                @empty
                    <div class="pipeline-empty">Tidak ada prospek.</div>
                @endforelse
            </div>
        </article>
    @endforeach
</section>
@endsection

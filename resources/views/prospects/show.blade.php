@extends('layouts.app')

@section('content')
    <section class="card">
        <div class="hero-panel">
            <div class="hero-copy">
                <h2>{{ $prospect->prospect_code }} - {{ $prospect->name }}</h2>
                <p>{{ $prospect->company ?: 'Belum ada nama perusahaan' }} | Owner: {{ $prospect->owner->name ?? '-' }}</p>
                <div class="info-strip">
                    <span class="badge info">{{ \App\Models\Prospect::ACCOUNT_CATEGORY_LABELS[$prospect->account_category] ?? strtoupper($prospect->account_category) }}</span>
                    <span class="badge">{{ $statusLabels[$prospect->status] ?? strtoupper($prospect->status) }}</span>
                </div>
            </div>
            <div class="mini-guide">
                <h3>Apa yang bisa dilakukan di sini</h3>
                <p><strong>Lihat detail:</strong> status, owner, nilai, dan follow up.</p>
                <p><strong>Input harian:</strong> tambahkan catatan aktivitas terbaru jika kamu punya hak edit.</p>
                @if($canEditProspect)
                    <div class="hero-actions" style="margin-top:12px;">
                        <a href="{{ route('prospects.edit', $prospect) }}" class="btn secondary">Edit Data Prospek</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="row" style="margin-top:10px;">
            <div class="kpi-box"><h4>Status</h4><strong>{{ $statusLabels[$prospect->status] ?? strtoupper($prospect->status) }}</strong></div>
            <div class="kpi-box"><h4>Kategori Akun</h4><strong>{{ \App\Models\Prospect::ACCOUNT_CATEGORY_LABELS[$prospect->account_category] ?? strtoupper($prospect->account_category) }}</strong></div>
            <div class="kpi-box"><h4>Prioritas</h4><strong>{{ $prospect->priority }}</strong></div>
            <div class="kpi-box"><h4>Follow Up</h4><strong>{{ $prospect->next_follow_up_date?->format('d M Y') ?: '-' }}</strong></div>
            <div class="kpi-box"><h4>Estimasi</h4><strong>Rp {{ number_format($prospect->estimation_value, 0, ',', '.') }}</strong></div>
        </div>

        @if($prospect->notes)
            <div class="soft-panel" style="margin-top:10px;"><strong>Catatan Internal:</strong><br>{{ $prospect->notes }}</div>
        @endif
    </section>

    @if($canEditProspect)
        <section class="card">
            <h3 style="margin-top:0;">Input Harian Baru</h3>
            <p class="form-section-copy">Gunakan form ini untuk mencatat aktivitas terbaru tanpa harus mengubah semua data prospek.</p>
            <form method="post" action="{{ route('prospects.update', $prospect) }}" class="grid">
                @csrf
                @method('put')
                <input type="hidden" name="name" value="{{ $prospect->name }}">
                <input type="hidden" name="company" value="{{ $prospect->company }}">
                <input type="hidden" name="phone" value="{{ $prospect->phone }}">
                <input type="hidden" name="email" value="{{ $prospect->email }}">
                <input type="hidden" name="source" value="{{ $prospect->source }}">
                <input type="hidden" name="account_category" value="{{ $prospect->account_category }}">
                <input type="hidden" name="status" value="{{ $prospect->status }}">
                <input type="hidden" name="priority" value="{{ $prospect->priority }}">
                <input type="hidden" name="estimation_value" value="{{ $prospect->estimation_value }}">
                <input type="hidden" name="next_follow_up_date" value="{{ $prospect->next_follow_up_date?->format('Y-m-d') }}">
                <input type="hidden" name="notes" value="{{ $prospect->notes }}">
                @if(!auth()->user()->isPenjualan())
                    <input type="hidden" name="owner_id" value="{{ $prospect->owner_id }}">
                @endif

                <div class="row">
                    <label>Jenis Aktivitas
                        <select name="daily_activity_type" required>
                            <option value="">Pilih</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}">{{ strtoupper($type) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Ringkasan
                        <input type="text" name="daily_summary" required>
                    </label>
                </div>
                <label>Hasil
                    <textarea name="daily_result"></textarea>
                </label>
                <button type="submit" class="btn">Simpan Input Harian</button>
            </form>
        </section>
    @endif

    <section class="card">
        <h3 style="margin-top:0;">Riwayat Input Harian</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Ringkasan</th>
                        <th>Hasil</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prospect->logs->sortByDesc('log_date') as $log)
                        <tr>
                            <td>{{ $log->log_date->format('d M Y') }}</td>
                            <td>{{ strtoupper($log->activity_type) }}</td>
                            <td>{{ $log->summary }}</td>
                            <td>{{ $log->result ?: '-' }}</td>
                            <td>{{ $log->user->name ?? '-' }}</td>
                        </tr>
                @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <strong>Belum ada riwayat aktivitas.</strong>
                                    Aktivitas follow up pertama akan muncul di sini setelah disimpan.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

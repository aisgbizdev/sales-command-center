@extends('layouts.app')

@section('content')
    <section class="card">
        <div class="hero-panel">
            <div class="hero-copy">
                <h2 class="page-title">{{ $review->title }}</h2>
                <p class="page-subtitle">
                    {{ $review->customer_name }}{{ $review->customer_company ? ' - ' . $review->customer_company : '' }}
                </p>
                <div class="info-strip">
                    <span class="badge info">{{ strtoupper($review->outcome) }}</span>
                    <span class="badge">{{ strtoupper($review->status) }}</span>
                    <span class="badge">{{ strtoupper($review->channel) }}</span>
                </div>
            </div>
            <div class="mini-guide">
                <h3>Apa yang bisa dilakukan di sini</h3>
                <p><strong>Lihat detail:</strong> outcome, status, kanal, dan ringkasan obrolan.</p>
                @if($canEditReview)
                    <div class="hero-actions" style="margin-top:12px;">
                        <a href="{{ route('chat-reviews.edit', $review) }}" class="btn secondary">Edit Review</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="row" style="margin-top:10px;">
            <div class="kpi-box"><h4>Pengirim</h4><strong>{{ $review->submitter->name ?? '-' }}</strong></div>
            <div class="kpi-box"><h4>Customer</h4><strong>{{ $review->customer_name ?: '-' }}</strong></div>
            <div class="kpi-box"><h4>Perusahaan</h4><strong>{{ $review->customer_company ?: '-' }}</strong></div>
        </div>

        <div class="stack" style="margin-top:10px;">
            <div class="soft-panel">
                <strong>Ringkasan</strong><br>
                {{ $review->chat_summary }}
            </div>

            @if($review->chat_excerpt)
                <div class="soft-panel">
                    <strong>Potongan Chat</strong><br>
                    {{ $review->chat_excerpt }}
                </div>
            @endif

            <div class="row">
                <div class="soft-panel">
                    <strong>Yang Berhasil</strong><br>
                    {{ $review->what_worked ?: '-' }}
                </div>
                <div class="soft-panel">
                    <strong>Yang Gagal</strong><br>
                    {{ $review->what_failed ?: '-' }}
                </div>
            </div>

            @if($review->suggested_knowledge_update)
                <div class="soft-panel">
                    <strong>Saran Update Pengetahuan GPT</strong><br>
                    {{ $review->suggested_knowledge_update }}
                </div>
            @endif
        </div>
    </section>

@if($canAddReviewNotes)
    <section class="card">
        <h3 style="margin-top:0;">Catatan Tinjauan Manajer</h3>
        <form method="post" action="{{ route('chat-reviews.manager-note', $review) }}" class="grid" style="margin-bottom:10px;">
            @csrf
            <div class="row">
                <label>Tag
                    <select name="tag" required>
                        <option value="general">General</option>
                        <option value="win_pattern">Win Pattern</option>
                        <option value="loss_pattern">Loss Pattern</option>
                        <option value="coaching">Coaching</option>
                        <option value="gpt_update">GPT Update</option>
                    </select>
                </label>
            </div>
            <label>Catatan
                <textarea name="note" required></textarea>
            </label>
            <button type="submit" class="btn">Tambah Catatan</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Tanggal</th><th>Tag</th><th>Catatan</th><th>Reviewer</th></tr></thead>
                <tbody>
                    @forelse($review->managerNotes as $note)
                        <tr>
                            <td>{{ $note->created_at->format('d M Y H:i') }}</td>
                            <td>{{ strtoupper($note->tag) }}</td>
                            <td>{{ $note->note }}</td>
                            <td>{{ $note->reviewer->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Belum ada catatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h3 style="margin-top:0;">Masukkan ke Antrian Pembaruan Pengetahuan</h3>
        <form method="post" action="{{ route('knowledge-queue.store', $review) }}" class="grid">
            @csrf
            <div class="row">
                <label>Prioritas
                    <select name="priority" required>
                        @foreach($queuePriorities as $priority)
                            <option value="{{ $priority }}">{{ strtoupper($priority) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <label>Pola Masalah / Peluang
                <textarea name="problem_pattern" required></textarea>
            </label>
            <label>Rekomendasi Update Pengetahuan GPT
                <textarea name="recommended_update" required></textarea>
            </label>
            <label>Dampak yang Diharapkan
                <textarea name="expected_impact"></textarea>
            </label>
            <button type="submit" class="btn">Kirim ke Antrian</button>
        </form>
    </section>
@endif

    <section class="card">
        <h3 style="margin-top:0;">Riwayat Antrian Pengetahuan</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tanggal</th><th>Prioritas</th><th>Status</th><th>Requester</th><th>Reviewer</th><th>Catatan Super Admin</th></tr></thead>
                <tbody>
                    @forelse($review->knowledgeQueues as $queue)
                        <tr>
                            <td>{{ $queue->created_at->format('d M Y H:i') }}</td>
                            <td>{{ strtoupper($queue->priority) }}</td>
                            <td><span class="badge">{{ strtoupper($queue->status) }}</span></td>
                            <td>{{ $queue->requester->name ?? '-' }}</td>
                            <td>{{ $queue->reviewer->name ?? '-' }}</td>
                            <td>{{ $queue->super_admin_note ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Belum ada antrian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

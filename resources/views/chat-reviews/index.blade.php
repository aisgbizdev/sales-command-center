@extends('layouts.app')

@section('content')
@php
    $reviewStatusClass = static fn (string $status): string => match ($status) {
        'draft' => 'status-draft',
        'in_review' => 'status-in-review',
        'queued_for_approval' => 'status-queued-for-approval',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'info',
    };
@endphp
<section class="card">
    <div class="section-head">
        <div>
            <h2 class="page-title">Pusat Tinjauan Obrolan</h2>
            <p class="page-subtitle">Tangkap pola percakapan yang berhasil dan gagal.</p>
        </div>
        <a class="btn" href="{{ route('chat-reviews.create') }}">+ Kasus Obrolan</a>
    </div>

    <form method="get" class="filters compact">
        <input name="q" value="{{ request('q') }}" placeholder="Cari judul / customer / perusahaan">
        <select name="account_category">
            <option value="">Semua Kategori Akun</option>
            @foreach($accountCategories as $accountCategory)
                <option value="{{ $accountCategory }}" @selected(request('account_category') === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
            @endforeach
        </select>
        <select name="outcome">
            <option value="">Semua Outcome</option>
            @foreach($outcomes as $outcome)
                <option value="{{ $outcome }}" @selected(request('outcome') === $outcome)>{{ strtoupper($outcome) }}</option>
            @endforeach
        </select>
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
                    <th>Kasus</th>
                    <th>Customer</th>
                    <th>Outcome</th>
                    <th>Status</th>
                    <th>Pengirim</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td>{{ $review->title }}<br><small>{{ strtoupper($review->channel) }}</small></td>
                        <td>{{ $review->customer_name }}<br>{{ $review->customer_company ?: '-' }}</td>
                        <td><span class="badge {{ $review->outcome === 'berhasil' ? 'success' : ($review->outcome === 'gagal' ? 'danger' : 'info') }}">{{ strtoupper($review->outcome) }}</span></td>
                        <td><span class="badge {{ $reviewStatusClass($review->status) }}">{{ strtoupper($review->status) }}</span></td>
                        <td>{{ $review->submitter->name ?? '-' }}</td>
                        <td>{{ $review->created_at->format('d M Y') }}</td>
                        <td><a class="btn secondary" href="{{ route('chat-reviews.show', $review) }}">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada kasus obrolan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">{{ $reviews->links() }}</div>
</section>
@endsection

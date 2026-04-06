@extends('layouts.app')

@section('content')
@php
    $queueStatusClass = static fn (string $status): string => match ($status) {
        'queued' => 'status-queued',
        'in_review' => 'status-in-review',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'info',
    };
@endphp
<section class="card">
    <div class="section-head">
        <div>
            <h2 class="page-title">Antrian Pembaruan Pengetahuan</h2>
            <p class="page-subtitle">Pantau alur approval pembelajaran untuk update knowledge GPT.</p>
        </div>
    </div>

    <form method="get" class="filters compact">
        <select name="account_category">
            <option value="">Semua Kategori Akun</option>
            @foreach($accountCategories as $accountCategory)
                <option value="{{ $accountCategory }}" @selected(request('account_category') === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
            @endforeach
        </select>
        <select name="priority">
            <option value="">Semua Prioritas</option>
            @foreach($priorities as $priority)
                <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ strtoupper($priority) }}</option>
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
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th>Requester</th>
                    <th>Reviewer</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($queues as $queue)
                    <tr>
                        <td>
                            {{ $queue->chatReview->title ?? '-' }}<br>
                            <small>{{ $queue->problem_pattern }}</small>
                        </td>
                        <td>{{ strtoupper($queue->priority) }}</td>
                        <td><span class="badge {{ $queueStatusClass($queue->status) }}">{{ strtoupper($queue->status) }}</span></td>
                        <td>{{ $queue->requester->name ?? '-' }}</td>
                        <td>{{ $queue->reviewer->name ?? '-' }}</td>
                        <td>
                            @if(auth()->user()->isSuperAdmin())
                                <div style="display:grid; gap:6px; min-width:220px;">
                                    @if($queue->status === 'queued')
                                        <form method="post" action="{{ route('knowledge-queue.set-review', $queue) }}">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" class="btn secondary" style="width:100%;">Set In Review</button>
                                        </form>
                                    @endif

                                    @if($queue->status !== 'approved')
                                        <form method="post" action="{{ route('knowledge-queue.approve', $queue) }}" class="grid">
                                            @csrf
                                            @method('patch')
                                            <input type="text" name="super_admin_note" placeholder="Catatan approve (opsional)">
                                            <button type="submit" class="btn" style="width:100%;">Approve</button>
                                        </form>
                                    @endif

                                    @if($queue->status !== 'rejected')
                                        <form method="post" action="{{ route('knowledge-queue.reject', $queue) }}" class="grid">
                                            @csrf
                                            @method('patch')
                                            <input type="text" name="super_admin_note" placeholder="Alasan reject" required>
                                            <button type="submit" class="btn danger" style="width:100%;">Reject</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada item antrian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">{{ $queues->links() }}</div>
</section>
@endsection

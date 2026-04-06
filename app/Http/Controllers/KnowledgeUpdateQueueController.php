<?php

namespace App\Http\Controllers;

use App\Models\ChatReview;
use App\Models\KnowledgeUpdateQueue;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class KnowledgeUpdateQueueController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', KnowledgeUpdateQueue::class);
        $queues = $this->scopedQueues($request->user())
            ->with([
                'chatReview:id,title,outcome,status,submitted_by',
                'chatReview.submitter:id,name',
                'requester:id,name',
                'reviewer:id,name',
            ])
            ->when($request->string('status')->toString(), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->string('priority')->toString(), fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($request->string('account_category')->toString(), function (Builder $q, string $accountCategory) {
                $q->whereHas('chatReview.prospect', fn (Builder $inner) => $inner->where('account_category', $accountCategory));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('knowledge-queue.index', [
            'queues' => $queues,
            'statuses' => KnowledgeUpdateQueue::STATUSES,
            'priorities' => KnowledgeUpdateQueue::PRIORITIES,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
        ]);
    }

    public function store(Request $request, ChatReview $chatReview)
    {
        $user = $request->user();
        $this->authorize('create', KnowledgeUpdateQueue::class);
        $this->authorize('markImportant', $chatReview);

        $validated = $request->validate([
            'priority' => ['required', 'in:'.implode(',', KnowledgeUpdateQueue::PRIORITIES)],
            'problem_pattern' => ['required', 'string'],
            'recommended_update' => ['required', 'string'],
            'expected_impact' => ['nullable', 'string'],
        ]);

        KnowledgeUpdateQueue::create([
            ...$validated,
            'status' => 'queued',
            'chat_review_id' => $chatReview->id,
            'requested_by' => $user->id,
        ]);

        if ($chatReview->status !== 'approved') {
            $chatReview->update(['status' => 'queued_for_approval']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Kasus masuk antrian pembaruan pengetahuan.',
            ]);
        }

        return back()->with('status', 'Kasus masuk antrian pembaruan pengetahuan.');
    }

    public function setReview(Request $request, KnowledgeUpdateQueue $knowledgeQueue)
    {
        $this->authorize('approve', $knowledgeQueue);

        $knowledgeQueue->update([
            'status' => 'in_review',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Antrian dipindahkan ke tahap review Super Admin.',
            ]);
        }

        return back()->with('status', 'Antrian dipindahkan ke tahap review Super Admin.');
    }

    public function approve(Request $request, KnowledgeUpdateQueue $knowledgeQueue)
    {
        $this->authorize('approve', $knowledgeQueue);

        $validated = $request->validate([
            'super_admin_note' => ['nullable', 'string'],
        ]);

        $knowledgeQueue->update([
            'status' => 'approved',
            'super_admin_note' => $validated['super_admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $knowledgeQueue->chatReview->update(['status' => 'approved']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Antrian disetujui Super Admin.',
            ]);
        }

        return back()->with('status', 'Antrian disetujui Super Admin.');
    }

    public function reject(Request $request, KnowledgeUpdateQueue $knowledgeQueue)
    {
        $this->authorize('approve', $knowledgeQueue);

        $validated = $request->validate([
            'super_admin_note' => ['required', 'string'],
        ]);

        $knowledgeQueue->update([
            'status' => 'rejected',
            'super_admin_note' => $validated['super_admin_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $knowledgeQueue->chatReview->update(['status' => 'rejected']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Antrian ditolak Super Admin.',
            ]);
        }

        return back()->with('status', 'Antrian ditolak Super Admin.');
    }

    private function scopedQueues(User $user): Builder
    {
        return KnowledgeUpdateQueue::query()
            ->when($user->isKepala(), function (Builder $q) use ($user) {
                $q->whereHas('chatReview.submitter', fn (Builder $sq) => $sq->where('unit_id', $user->unit_id));
            });
    }
}

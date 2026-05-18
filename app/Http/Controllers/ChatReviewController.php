<?php

namespace App\Http\Controllers;

use App\Models\ChatReview;
use App\Models\ManagerReviewNote;
use App\Models\Prospect;
use App\Models\ProspectLog;
use App\Models\User;
use App\Support\RoleScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ChatReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ChatReview::class);
        $reviews = $this->scopedReviews($request->user())
            ->with(['submitter:id,name,role', 'prospect:id,name,prospect_code'])
            ->when($request->string('outcome')->toString(), fn (Builder $q, string $outcome) => $q->where('outcome', $outcome))
            ->when($request->string('status')->toString(), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->string('account_category')->toString(), function (Builder $q, string $accountCategory) {
                $q->whereHas('prospect', fn (Builder $inner) => $inner->where('account_category', $accountCategory));
            })
            ->when($request->string('q')->toString(), function (Builder $q, string $keyword) {
                $q->where(function (Builder $inner) use ($keyword) {
                    $inner->where('title', 'like', "%{$keyword}%")
                        ->orWhere('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_company', 'like', "%{$keyword}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('chat-reviews.index', [
            'reviews' => $reviews,
            'outcomes' => ChatReview::OUTCOMES,
            'statuses' => ChatReview::STATUSES,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', ChatReview::class);

        return view('chat-reviews.create', [
            'outcomes' => ChatReview::OUTCOMES,
            'channels' => ChatReview::CHANNELS,
            'prospects' => $this->scopedProspects($request->user())
                ->orderBy('name')
                ->get(['id', 'name', 'prospect_code']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', ChatReview::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'channel' => ['required', 'in:'.implode(',', ChatReview::CHANNELS)],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_company' => ['nullable', 'string', 'max:120'],
            'outcome' => ['required', 'in:'.implode(',', ChatReview::OUTCOMES)],
            'objection_type' => ['nullable', 'in:'.implode(',', ProspectLog::OBJECTION_TYPES)],
            'objection_detail' => ['nullable', 'string'],
            'emotional_state' => ['nullable', 'in:'.implode(',', ProspectLog::EMOTIONAL_STATES)],
            'chat_summary' => ['required', 'string'],
            'chat_excerpt' => ['nullable', 'string'],
            'what_worked' => ['nullable', 'string'],
            'what_failed' => ['nullable', 'string'],
            'suggested_knowledge_update' => ['nullable', 'string'],
            'prospect_id' => ['nullable', 'exists:prospects,id'],
        ]);

        if (! empty($validated['prospect_id'])) {
            $prospect = Prospect::findOrFail($validated['prospect_id']);
            $this->ensureProspectVisible($prospect, $request->user());
        }

        ChatReview::create([
            ...$validated,
            'submitted_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return redirect()->route('chat-reviews.index')->with('status', 'Kasus obrolan berhasil disimpan.');
    }

    public function show(Request $request, ChatReview $chatReview)
    {
        $user = $request->user();
        $this->authorize('view', $chatReview);

        return view('chat-reviews.show', [
            'review' => $chatReview->load([
                'submitter:id,name,role',
                'prospect:id,name,prospect_code',
                'managerNotes.reviewer:id,name,role',
                'knowledgeQueues.requester:id,name',
                'knowledgeQueues.reviewer:id,name',
            ]),
            'queuePriorities' => \App\Models\KnowledgeUpdateQueue::PRIORITIES,
            'queueStatuses' => \App\Models\KnowledgeUpdateQueue::STATUSES,
            'canEditReview' => $user->can('update', $chatReview),
            'canAddReviewNotes' => $user->can('comment', $chatReview),
        ]);
    }

    public function edit(Request $request, ChatReview $chatReview)
    {
        $this->authorize('update', $chatReview);

        return view('chat-reviews.edit', [
            'review' => $chatReview,
            'outcomes' => ChatReview::OUTCOMES,
            'channels' => ChatReview::CHANNELS,
            'prospects' => $this->scopedProspects($request->user())
                ->orderBy('name')
                ->get(['id', 'name', 'prospect_code']),
        ]);
    }

    public function update(Request $request, ChatReview $chatReview)
    {
        $this->authorize('update', $chatReview);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'channel' => ['required', 'in:'.implode(',', ChatReview::CHANNELS)],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_company' => ['nullable', 'string', 'max:120'],
            'outcome' => ['required', 'in:'.implode(',', ChatReview::OUTCOMES)],
            'objection_type' => ['nullable', 'in:'.implode(',', ProspectLog::OBJECTION_TYPES)],
            'objection_detail' => ['nullable', 'string'],
            'emotional_state' => ['nullable', 'in:'.implode(',', ProspectLog::EMOTIONAL_STATES)],
            'status' => ['required', 'in:'.implode(',', ChatReview::STATUSES)],
            'chat_summary' => ['required', 'string'],
            'chat_excerpt' => ['nullable', 'string'],
            'what_worked' => ['nullable', 'string'],
            'what_failed' => ['nullable', 'string'],
            'suggested_knowledge_update' => ['nullable', 'string'],
            'prospect_id' => ['nullable', 'exists:prospects,id'],
        ]);

        if (! empty($validated['prospect_id'])) {
            $prospect = Prospect::findOrFail($validated['prospect_id']);
            $this->ensureProspectVisible($prospect, $request->user());
        }

        $chatReview->update($validated);

        return redirect()->route('chat-reviews.show', $chatReview)->with('status', 'Kasus obrolan berhasil diperbarui.');
    }

    public function destroy(Request $request, ChatReview $chatReview)
    {
        $this->authorize('delete', $chatReview);
        $chatReview->delete();

        return redirect()->route('chat-reviews.index')->with('status', 'Kasus obrolan dihapus.');
    }

    public function addManagerNote(Request $request, ChatReview $chatReview)
    {
        $user = $request->user();
        $this->authorize('comment', $chatReview);

        $validated = $request->validate([
            'tag' => ['required', 'in:general,win_pattern,loss_pattern,coaching,gpt_update'],
            'note' => ['required', 'string'],
        ]);

        ManagerReviewNote::create([
            ...$validated,
            'chat_review_id' => $chatReview->id,
            'reviewed_by' => $user->id,
        ]);

        if ($chatReview->status === 'draft') {
            $chatReview->update(['status' => 'in_review']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Catatan manajer berhasil ditambahkan.',
            ]);
        }

        return back()->with('status', 'Catatan manajer berhasil ditambahkan.');
    }

    private function scopedReviews(User $user): Builder
    {
        return RoleScope::forSubmitterTeamUnit(ChatReview::query(), $user);
    }

    private function scopedProspects(User $user): Builder
    {
        return RoleScope::forOwnerTeamUnit(Prospect::query(), $user);
    }

    private function ensureProspectVisible(Prospect $prospect, User $user): void
    {
        if (! $user->can('view', $prospect)) {
            abort(403);
        }
    }
}

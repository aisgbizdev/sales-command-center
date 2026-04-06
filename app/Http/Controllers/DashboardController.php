<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $accountCategory = $request->string('account_category')->toString();
        $prospects = Prospect::query()
            ->when($accountCategory, fn ($q) => $q->where('account_category', $accountCategory));

        if ($user->isPenjualan()) {
            $prospects->where('owner_id', $user->id);
        } elseif ($user->isManager()) {
            $prospects->where('team_id', $user->team_id);
        } elseif ($user->isKepala()) {
            $prospects->where('unit_id', $user->unit_id);
        }

        $totalProspects = (clone $prospects)->count();
        $openProspects = (clone $prospects)
            ->whereIn('status', [
                Prospect::STATUS_BARU,
                Prospect::STATUS_DIHUBUNGI,
                Prospect::STATUS_DIBALAS,
                Prospect::STATUS_SEDANG_BERJALAN,
                Prospect::STATUS_TINDAK_LANJUT,
                Prospect::STATUS_PENUTUPAN,
            ])
            ->count();
        $wonProspects = (clone $prospects)->where('status', Prospect::STATUS_PENUTUPAN)->count();
        $totalEstimation = (clone $prospects)->where('status', '!=', Prospect::STATUS_HILANG)->sum('estimation_value');

        $overdueCount = (clone $prospects)
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->whereDate('next_follow_up_date', '<', now()->toDateString())
            ->count();

        $dueTodayCount = (clone $prospects)
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->whereDate('next_follow_up_date', now()->toDateString())
            ->count();

        $todayInputQuery = ProspectLog::query()->whereDate('log_date', now()->toDateString());
        if ($user->isPenjualan()) {
            $todayInputQuery->where('user_id', $user->id);
        } elseif ($user->isManager()) {
            $todayInputQuery->whereHas('user', fn ($q) => $q->where('team_id', $user->team_id));
        } elseif ($user->isKepala()) {
            $todayInputQuery->whereHas('user', fn ($q) => $q->where('unit_id', $user->unit_id));
        }
        $todayInputCount = $todayInputQuery->count();

        $statusSummary = (clone $prospects)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $upcomingFollowUp = (clone $prospects)
            ->with('owner:id,name')
            ->whereNotNull('next_follow_up_date')
            ->orderBy('next_follow_up_date')
            ->limit(8)
            ->get();

        $recentInputs = ProspectLog::query()
            ->with(['prospect:id,name,prospect_code', 'user:id,name'])
            ->latest('log_date')
            ->latest('id')
            ->limit(8);

        if ($user->isPenjualan()) {
            $recentInputs->where('user_id', $user->id);
        } elseif ($user->isManager()) {
            $recentInputs->whereHas('user', fn ($q) => $q->where('team_id', $user->team_id));
        } elseif ($user->isKepala()) {
            $recentInputs->whereHas('user', fn ($q) => $q->where('unit_id', $user->unit_id));
        }

        return view('dashboard', [
            'totalProspects' => $totalProspects,
            'openProspects' => $openProspects,
            'wonProspects' => $wonProspects,
            'totalEstimation' => $totalEstimation,
            'overdueCount' => $overdueCount,
            'dueTodayCount' => $dueTodayCount,
            'todayInputCount' => $todayInputCount,
            'statusSummary' => $statusSummary,
            'upcomingFollowUp' => $upcomingFollowUp,
            'recentInputs' => $recentInputs->get(),
            'statusLabels' => Prospect::STATUS_LABELS,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
            'selectedAccountCategory' => $accountCategory,
        ]);
    }
}

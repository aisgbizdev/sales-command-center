<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesActivity;
use App\Models\User;
use Illuminate\Http\Request;

class SalesActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SalesActivity::query()->with(['customer:id,name', 'salesUser:id,name']);
        $user = $request->user();

        if ($user->isSales()) {
            $query->where('sales_user_id', $user->id);
        }

        $activities = $query->latest('activity_date')->paginate(12);

        return view('activities.index', [
            'activities' => $activities,
            'types' => SalesActivity::TYPES,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $customers = Customer::query()
            ->select('id', 'name')
            ->when($request->user()->isSales(), fn ($query) => $query->where('created_by', $request->user()->id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $salesUsers = User::query()->where('role', User::ROLE_SALES)->orderBy('name')->get(['id', 'name']);

        return view('activities.create', [
            'customers' => $customers,
            'salesUsers' => $salesUsers,
            'types' => SalesActivity::TYPES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'activity_date' => ['required', 'date'],
            'activity_type' => ['required', 'in:'.implode(',', SalesActivity::TYPES)],
            'summary' => ['required', 'string', 'max:200'],
            'outcome' => ['nullable', 'string'],
            'next_follow_up_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'sales_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['sales_user_id'] = $user->isSales() ? $user->id : ($validated['sales_user_id'] ?? null);
        if (! $validated['sales_user_id']) {
            return back()->withErrors(['sales_user_id' => 'Sales harus dipilih.'])->withInput();
        }

        SalesActivity::create($validated);

        return redirect()->route('activities.index')->with('status', 'Aktivitas berhasil dicatat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesActivity $activity)
    {
        return redirect()->route('activities.edit', $activity);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, SalesActivity $activity)
    {
        if ($request->user()->isSales() && $activity->sales_user_id !== $request->user()->id) {
            abort(403);
        }

        $customers = Customer::query()
            ->select('id', 'name')
            ->when($request->user()->isSales(), fn ($query) => $query->where('created_by', $request->user()->id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $salesUsers = User::query()->where('role', User::ROLE_SALES)->orderBy('name')->get(['id', 'name']);

        return view('activities.edit', [
            'activity' => $activity,
            'customers' => $customers,
            'salesUsers' => $salesUsers,
            'types' => SalesActivity::TYPES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesActivity $activity)
    {
        $user = $request->user();
        if ($user->isSales() && $activity->sales_user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'activity_date' => ['required', 'date'],
            'activity_type' => ['required', 'in:'.implode(',', SalesActivity::TYPES)],
            'summary' => ['required', 'string', 'max:200'],
            'outcome' => ['nullable', 'string'],
            'next_follow_up_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'sales_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($user->isSales()) {
            $validated['sales_user_id'] = $user->id;
        } elseif (empty($validated['sales_user_id'])) {
            return back()->withErrors(['sales_user_id' => 'Sales harus dipilih.'])->withInput();
        }

        $activity->update($validated);

        return redirect()->route('activities.index')->with('status', 'Aktivitas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, SalesActivity $activity)
    {
        if ($request->user()->isSales() && $activity->sales_user_id !== $request->user()->id) {
            abort(403);
        }

        $activity->delete();

        return redirect()->route('activities.index')->with('status', 'Aktivitas berhasil dihapus.');
    }
}

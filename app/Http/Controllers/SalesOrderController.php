<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = SalesOrder::query()->with(['customer:id,name', 'salesUser:id,name']);

        if ($user->isSales()) {
            $query->where('sales_user_id', $user->id);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $orders = $query->latest('order_date')->paginate(12)->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'statuses' => SalesOrder::STATUSES,
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

        return view('orders.create', [
            'customers' => $customers,
            'salesUsers' => $salesUsers,
            'statuses' => SalesOrder::STATUSES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'customer_id' => ['required', 'exists:customers,id'],
            'status' => ['required', 'in:'.implode(',', SalesOrder::STATUSES)],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'sales_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $salesUserId = $user->isSales() ? $user->id : ($validated['sales_user_id'] ?? null);
        if (! $salesUserId) {
            return back()->withErrors(['sales_user_id' => 'Sales harus dipilih.'])->withInput();
        }

        SalesOrder::create([
            ...$validated,
            'sales_user_id' => $salesUserId,
            'order_number' => 'SO-'.now()->format('Ymd').'-'.str_pad((string) (SalesOrder::whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT),
        ]);

        return redirect()->route('orders.index')->with('status', 'Order penjualan berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesOrder $order)
    {
        return redirect()->route('orders.edit', $order);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, SalesOrder $order)
    {
        if ($request->user()->isSales() && $order->sales_user_id !== $request->user()->id) {
            abort(403);
        }

        $customers = Customer::query()
            ->select('id', 'name')
            ->when($request->user()->isSales(), fn ($query) => $query->where('created_by', $request->user()->id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $salesUsers = User::query()->where('role', User::ROLE_SALES)->orderBy('name')->get(['id', 'name']);

        return view('orders.edit', [
            'order' => $order,
            'customers' => $customers,
            'salesUsers' => $salesUsers,
            'statuses' => SalesOrder::STATUSES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesOrder $order)
    {
        $user = $request->user();
        if ($user->isSales() && $order->sales_user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'customer_id' => ['required', 'exists:customers,id'],
            'status' => ['required', 'in:'.implode(',', SalesOrder::STATUSES)],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'sales_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($user->isSales()) {
            $validated['sales_user_id'] = $user->id;
        } elseif (empty($validated['sales_user_id'])) {
            return back()->withErrors(['sales_user_id' => 'Sales harus dipilih.'])->withInput();
        }

        $order->update($validated);

        return redirect()->route('orders.index')->with('status', 'Order penjualan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, SalesOrder $order)
    {
        if ($request->user()->isSales() && $order->sales_user_id !== $request->user()->id) {
            abort(403);
        }

        $order->delete();

        return redirect()->route('orders.index')->with('status', 'Order penjualan berhasil dihapus.');
    }
}

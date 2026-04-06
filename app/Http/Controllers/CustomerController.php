<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Customer::query()->with('creator:id,name');

        if ($user->isSales()) {
            $query->where('created_by', $user->id);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(12)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        Customer::create([
            ...$validated,
            'customer_code' => 'CUST-'.str_pad((string) (Customer::max('id') + 1), 5, '0', STR_PAD_LEFT),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return redirect()->route('customers.edit', $customer);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Customer $customer)
    {
        if ($request->user()->isSales() && $customer->created_by !== $request->user()->id) {
            abort(403);
        }

        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        if ($request->user()->isSales() && $customer->created_by !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Customer $customer)
    {
        if ($request->user()->isSales() && $customer->created_by !== $request->user()->id) {
            abort(403);
        }

        if ($customer->salesOrders()->exists()) {
            return redirect()->route('customers.index')->with('status', 'Pelanggan tidak bisa dihapus karena sudah punya transaksi.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil dihapus.');
    }
}

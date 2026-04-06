<div class="card">
    <div class="row">
        <label>Tanggal Order
            <input type="date" name="order_date" value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : now()->toDateString()) }}" required>
        </label>
        <label>Pelanggan
            <select name="customer_id" required>
                <option value="">Pilih pelanggan</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $order->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="status" required>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $order->status ?? 'draft') === $status)>{{ strtoupper($status) }}</option>
                @endforeach
            </select>
        </label>
        <label>Total Nilai
            <input type="number" min="0" step="0.01" name="total_amount" value="{{ old('total_amount', $order->total_amount ?? 0) }}" required>
        </label>

        @if(!auth()->user()->isSales())
            <label>Sales
                <select name="sales_user_id" required>
                    <option value="">Pilih sales</option>
                    @foreach($salesUsers as $sales)
                        <option value="{{ $sales->id }}" @selected((string) old('sales_user_id', $order->sales_user_id ?? '') === (string) $sales->id)>{{ $sales->name }}</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>
    <label>Catatan
        <textarea name="notes">{{ old('notes', $order->notes ?? '') }}</textarea>
    </label>
</div>

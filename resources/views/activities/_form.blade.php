<div class="card">
    <div class="row">
        <label>Tanggal Aktivitas
            <input type="date" name="activity_date" value="{{ old('activity_date', isset($activity) ? $activity->activity_date->format('Y-m-d') : now()->toDateString()) }}" required>
        </label>
        <label>Tipe Aktivitas
            <select name="activity_type" required>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(old('activity_type', $activity->activity_type ?? 'follow_up') === $type)>{{ strtoupper($type) }}</option>
                @endforeach
            </select>
        </label>
        <label>Pelanggan (opsional)
            <select name="customer_id">
                <option value="">- Tanpa pelanggan -</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $activity->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
        </label>

        @if(!auth()->user()->isSales())
            <label>Sales
                <select name="sales_user_id" required>
                    <option value="">Pilih sales</option>
                    @foreach($salesUsers as $sales)
                        <option value="{{ $sales->id }}" @selected((string) old('sales_user_id', $activity->sales_user_id ?? '') === (string) $sales->id)>{{ $sales->name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <label>Follow Up Berikutnya
            <input type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date', isset($activity) && $activity->next_follow_up_date ? $activity->next_follow_up_date->format('Y-m-d') : '') }}">
        </label>
    </div>

    <label>Ringkasan
        <input type="text" name="summary" value="{{ old('summary', $activity->summary ?? '') }}" required>
    </label>

    <label>Hasil / Catatan
        <textarea name="outcome">{{ old('outcome', $activity->outcome ?? '') }}</textarea>
    </label>
</div>

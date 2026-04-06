<div class="card">
    <div class="row">
        <label>Nama Pelanggan
            <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
        </label>
        <label>Telepon
            <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}">
        </label>
        <label>Email
            <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}">
        </label>
        <label>Status
            <select name="status" required>
                @foreach(['active' => 'Aktif', 'inactive' => 'Tidak Aktif'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $customer->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <label>Alamat
        <textarea name="address">{{ old('address', $customer->address ?? '') }}</textarea>
    </label>
</div>

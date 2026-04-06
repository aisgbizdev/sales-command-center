@extends('layouts.app')

@section('content')
    <h2>Edit Pelanggan</h2>
    <form method="post" action="{{ route('customers.update', $customer) }}" class="grid">
        @csrf
        @method('put')
        @include('customers._form')
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Update</button>
            <a href="{{ route('customers.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection

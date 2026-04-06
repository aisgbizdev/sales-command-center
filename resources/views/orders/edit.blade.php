@extends('layouts.app')

@section('content')
    <h2>Edit Order</h2>
    <form method="post" action="{{ route('orders.update', $order) }}" class="grid">
        @csrf
        @method('put')
        @include('orders._form')
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Update</button>
            <a href="{{ route('orders.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection

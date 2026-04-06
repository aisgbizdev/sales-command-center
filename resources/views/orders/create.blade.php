@extends('layouts.app')

@section('content')
    <h2>Tambah Order</h2>
    <form method="post" action="{{ route('orders.store') }}" class="grid">
        @csrf
        @include('orders._form')
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('orders.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection

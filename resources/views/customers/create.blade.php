@extends('layouts.app')

@section('content')
    <h2>Tambah Pelanggan</h2>
    <form method="post" action="{{ route('customers.store') }}" class="grid">
        @csrf
        @include('customers._form')
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('customers.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection

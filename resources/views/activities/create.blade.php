@extends('layouts.app')

@section('content')
    <h2>Tambah Aktivitas</h2>
    <form method="post" action="{{ route('activities.store') }}" class="grid">
        @csrf
        @include('activities._form')
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('activities.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection

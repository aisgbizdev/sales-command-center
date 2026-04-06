@extends('layouts.app')

@section('content')
<h2>Tambah Kasus Obrolan</h2>
<form method="post" action="{{ route('chat-reviews.store') }}" class="grid">
    @csrf
    @include('chat-reviews._form')
    <div style="display:flex; gap:8px;">
        <button type="submit" class="btn">Simpan</button>
        <a href="{{ route('chat-reviews.index') }}" class="btn secondary">Batal</a>
    </div>
</form>
@endsection

@extends('layouts.app')

@section('content')
<h2>Edit Kasus Obrolan</h2>
<form method="post" action="{{ route('chat-reviews.update', $review) }}" class="grid">
    @csrf
    @method('put')
    @include('chat-reviews._form')
    <div style="display:flex; gap:8px;">
        <button type="submit" class="btn">Update</button>
        <a href="{{ route('chat-reviews.show', $review) }}" class="btn secondary">Kembali</a>
    </div>
</form>
@endsection

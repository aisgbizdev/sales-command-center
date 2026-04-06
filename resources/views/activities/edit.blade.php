@extends('layouts.app')

@section('content')
    <h2>Edit Aktivitas</h2>
    <form method="post" action="{{ route('activities.update', $activity) }}" class="grid">
        @csrf
        @method('put')
        @include('activities._form')
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn">Update</button>
            <a href="{{ route('activities.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection

@extends('layouts.app')

@section('content')
    <section class="card">
        <div class="hero-panel">
            <div class="hero-copy">
                <h1>Perbarui Prospek</h1>
                <p>Halaman ini dipakai untuk merapikan data utama prospek. Kalau hanya ingin ubah status atau tambah catatan follow up, lebih cepat lewat halaman detail atau pipeline.</p>
                <div class="info-strip">
                    <span class="badge success">Edit data inti</span>
                    <span class="badge info">Flow cepat tetap tersedia di halaman lain</span>
                </div>
            </div>
            <div class="mini-guide">
                <h3>Supaya tidak bingung</h3>
                <p><strong>Data utama:</strong> nama, nomor, kategori akun, owner.</p>
                <p><strong>Data progres:</strong> status, follow up, catatan singkat.</p>
            </div>
        </div>
    </section>

    <form method="post" action="{{ route('prospects.update', $prospect) }}" class="grid">
        @csrf
        @method('put')
        @include('prospects._form')
        <div class="action-row">
            <button type="submit" class="btn">Simpan Perubahan</button>
            <a href="{{ route('prospects.show', $prospect) }}" class="btn secondary">Kembali ke Detail</a>
        </div>
    </form>
@endsection

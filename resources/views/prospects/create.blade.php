@extends('layouts.app')

@section('content')
    <section class="card">
        <div class="hero-panel">
            <div class="hero-copy">
                <h1>Tambah Prospek Baru</h1>
                <p>Isi data seperlunya saja. Fokus utama untuk user awam: nama prospek, nomor HP, kategori akun, status saat ini, dan jadwal follow up berikutnya.</p>
                <div class="info-strip">
                    <span class="badge info">Isi yang wajib dulu</span>
                    <span class="badge warn">Bisa dilengkapi nanti</span>
                </div>
            </div>
            <div class="mini-guide">
                <h3>Urutan paling aman</h3>
                <p><strong>1.</strong> Isi data dasar prospek.</p>
                <p><strong>2.</strong> Tentukan owner dan status saat ini.</p>
                <p><strong>3.</strong> Simpan, lalu lanjut input aktivitas harian jika sudah ada follow up.</p>
            </div>
        </div>
    </section>

    <form method="post" action="{{ route('prospects.store') }}" class="grid">
        @csrf
        @include('prospects._form')
        <div class="action-row">
            <button type="submit" class="btn">Simpan Prospek</button>
            <a href="{{ route('prospects.index') }}" class="btn secondary">Kembali ke Daftar</a>
        </div>
    </form>
@endsection

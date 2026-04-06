<div class="card">
    <h3 class="form-section-title">Data Utama Prospek</h3>
    <p class="form-section-copy">Bagian ini dipakai untuk menyimpan identitas prospek. Isi seperlunya dulu agar proses input tetap cepat.</p>

    <div class="stack">
        <label>Nama Prospek
            <input type="text" name="name" value="{{ old('name', $prospect->name ?? '') }}" placeholder="Contoh: Budi Santoso" required>
            <small class="field-help">Gunakan nama yang mudah dikenali oleh tim.</small>
        </label>
        <label>Perusahaan
            <input type="text" name="company" value="{{ old('company', $prospect->company ?? '') }}" placeholder="Opsional">
        </label>
        <label>Nomor HP / WhatsApp
            <input type="text" name="phone" value="{{ old('phone', $prospect->phone ?? '') }}" placeholder="Contoh: 0812xxxxxx">
            <small class="field-help">Gunakan nomor aktif agar mudah dicari saat follow up.</small>
        </label>
        <label>Email
            <input type="email" name="email" value="{{ old('email', $prospect->email ?? '') }}" placeholder="Opsional">
        </label>
    </div>

    <div class="soft-panel" style="margin-top:12px;">
        <h4 class="form-section-title">Klasifikasi Penjualan</h4>
        <p class="form-section-copy">Bagian ini dipakai untuk membedakan alur mini/reguler dan kualitas prospek.</p>

        <div class="stack">
            <label>Sumber
                <select name="source">
                    <option value="">-</option>
                    @foreach($sources as $source)
                        <option value="{{ $source }}" @selected(old('source', $prospect->source ?? '') === $source)>{{ strtoupper($source) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Kategori Akun
                <select name="account_category" required>
                    @foreach($accountCategories as $accountCategory)
                        <option value="{{ $accountCategory }}" @selected(old('account_category', $prospect->account_category ?? 'reguler') === $accountCategory)>{{ $accountCategoryLabels[$accountCategory] ?? strtoupper($accountCategory) }}</option>
                    @endforeach
                </select>
                <small class="field-help">Penting untuk filter Mini dan Regular di semua dashboard.</small>
            </label>
            <label>Status
                <select name="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $prospect->status ?? 'baru') === $status)>{{ $statusLabels[$status] ?? strtoupper($status) }}</option>
                    @endforeach
                </select>
                <small class="field-help">Pilih status paling dekat dengan kondisi prospek saat ini.</small>
            </label>
            <label>Mode GPT
                <select name="gpt_mode">
                    <option value="">Ikuti kategori akun</option>
                    @foreach($gptModes as $gptMode)
                        <option value="{{ $gptMode }}" @selected(old('gpt_mode', $prospect->gpt_mode ?? '') === $gptMode)>{{ $gptModeLabels[$gptMode] ?? strtoupper($gptMode) }}</option>
                    @endforeach
                </select>
            </label>
            <label>User Temperature
                <select name="user_temperature">
                    <option value="">-</option>
                    @foreach($userTemperatures as $temperature)
                        <option value="{{ $temperature }}" @selected(old('user_temperature', $prospect->user_temperature ?? '') === $temperature)>{{ $userTemperatureLabels[$temperature] ?? strtoupper($temperature) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Emosi Dominan
                <select name="dominant_emotion">
                    <option value="">-</option>
                    @foreach($dominantEmotions as $emotion)
                        <option value="{{ $emotion }}" @selected(old('dominant_emotion', $prospect->dominant_emotion ?? '') === $emotion)>{{ $dominantEmotionLabels[$emotion] ?? strtoupper($emotion) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="checkbox-inline">Kandidat Bridge
                <input type="checkbox" name="bridge_candidate" value="1" @checked((bool) old('bridge_candidate', $prospect->bridge_candidate ?? false))>
            </label>
            <label>Bridge Status
                <select name="bridge_status" required>
                    @foreach($bridgeStatuses as $bridgeStatus)
                        <option value="{{ $bridgeStatus }}" @selected(old('bridge_status', $prospect->bridge_status ?? 'none') === $bridgeStatus)>{{ $bridgeStatusLabels[$bridgeStatus] ?? strtoupper($bridgeStatus) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Lost Reason
                <select name="lost_reason">
                    <option value="">-</option>
                    @foreach($lostReasons as $lostReason)
                        <option value="{{ $lostReason }}" @selected(old('lost_reason', $prospect->lost_reason ?? '') === $lostReason)>{{ $lostReasonLabels[$lostReason] ?? strtoupper($lostReason) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Keberatan Utama
                <textarea name="main_objection" placeholder="Catat keberatan inti prospek untuk bahan review dan GPT">{{ old('main_objection', $prospect->main_objection ?? '') }}</textarea>
            </label>
        </div>
    </div>

    <div class="soft-panel" style="margin-top:12px;">
        <h4 class="form-section-title">Prioritas dan Follow Up</h4>
        <p class="form-section-copy">Bagian ini dipakai untuk kebutuhan monitoring harian dan reminder.</p>

        <div class="stack">
            <label>Prioritas
                <select name="priority" required>
                    <option value="1" @selected((int) old('priority', $prospect->priority ?? 2) === 1)>Tinggi</option>
                    <option value="2" @selected((int) old('priority', $prospect->priority ?? 2) === 2)>Sedang</option>
                    <option value="3" @selected((int) old('priority', $prospect->priority ?? 2) === 3)>Rendah</option>
                </select>
            </label>
            <label>Estimasi Nilai Potensi
                <input type="number" min="0" step="0.01" name="estimation_value" value="{{ old('estimation_value', $prospect->estimation_value ?? 0) }}">
                <small class="field-help">Kosongkan atau isi `0` jika belum ada estimasi yang pasti.</small>
            </label>
            <label>Tanggal Follow Up Berikutnya
                <input type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date', isset($prospect) && $prospect->next_follow_up_date ? $prospect->next_follow_up_date->format('Y-m-d') : '') }}">
                <small class="field-help">Tanggal ini dipakai untuk reminder dan deteksi overdue.</small>
            </label>

            @if(!auth()->user()->isPenjualan())
                <label>Owner (Penjualan)
                    <select name="owner_id" required>
                        <option value="">Pilih owner</option>
                        @foreach($salesUsers as $sales)
                            <option value="{{ $sales->id }}" @selected((string) old('owner_id', $prospect->owner_id ?? '') === (string) $sales->id)>{{ $sales->name }}</option>
                        @endforeach
                    </select>
                    <small class="field-help">Pilih sales yang akan bertanggung jawab atas prospek ini.</small>
                </label>
            @endif
        </div>
    </div>

    <div class="soft-panel" style="margin-top:12px;">
        <h4 class="form-section-title">Catatan Internal</h4>
        <p class="form-section-copy">Ringkasan tambahan untuk tim internal.</p>

        <div class="stack">
            <label>Catatan Internal Prospek
                <textarea name="notes" placeholder="Catatan singkat untuk tim internal">{{ old('notes', $prospect->notes ?? '') }}</textarea>
            </label>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="form-section-title">Input Harian Opsional</h3>
    <p class="form-section-copy">Bagian ini boleh dikosongkan jika kamu hanya ingin menyimpan data prospek dulu. Isi saat sudah ada aktivitas follow up.</p>
    <div class="row">
        <label>Jenis Aktivitas
            <select name="daily_activity_type">
                <option value="">-</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(old('daily_activity_type') === $type)>{{ strtoupper($type) }}</option>
                @endforeach
            </select>
        </label>
        <label>Ringkasan Aktivitas
            <input type="text" name="daily_summary" value="{{ old('daily_summary') }}" placeholder="Contoh: Follow up via telepon, prospek minta proposal">
        </label>
    </div>
    <label>Hasil Aktivitas
        <textarea name="daily_result" placeholder="Contoh: Prospek tertarik, minta dijadwalkan follow up ulang">{{ old('daily_result') }}</textarea>
    </label>
</div>

<div class="card">
    <div class="row">
        <label>Judul Kasus
            <input type="text" name="title" value="{{ old('title', $review->title ?? '') }}" required>
        </label>
        <label>Kanal Obrolan
            <select name="channel" required>
                @foreach($channels as $channel)
                    <option value="{{ $channel }}" @selected(old('channel', $review->channel ?? 'whatsapp') === $channel)>{{ strtoupper($channel) }}</option>
                @endforeach
            </select>
        </label>
        <label>Outcome
            <select name="outcome" required>
                @foreach($outcomes as $outcome)
                    <option value="{{ $outcome }}" @selected(old('outcome', $review->outcome ?? 'netral') === $outcome)>{{ strtoupper($outcome) }}</option>
                @endforeach
            </select>
        </label>
        @isset($review)
            <label>Status
                <select name="status" required>
                    @foreach(\App\Models\ChatReview::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $review->status) === $status)>{{ strtoupper($status) }}</option>
                    @endforeach
                </select>
            </label>
        @endisset
    </div>

    <div class="row">
        <label>Nama Customer
            <input type="text" name="customer_name" value="{{ old('customer_name', $review->customer_name ?? '') }}" required>
        </label>
        <label>Perusahaan
            <input type="text" name="customer_company" value="{{ old('customer_company', $review->customer_company ?? '') }}">
        </label>
        <label>Prospek Terkait
            <select name="prospect_id">
                <option value="">- Tidak terkait -</option>
                @foreach($prospects as $prospect)
                    <option value="{{ $prospect->id }}" @selected((string) old('prospect_id', $review->prospect_id ?? '') === (string) $prospect->id)>{{ $prospect->prospect_code }} - {{ $prospect->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <label>Ringkasan Obrolan
        <textarea name="chat_summary" required>{{ old('chat_summary', $review->chat_summary ?? '') }}</textarea>
    </label>
    <label>Potongan Chat Penting
        <textarea name="chat_excerpt">{{ old('chat_excerpt', $review->chat_excerpt ?? '') }}</textarea>
    </label>

    <div class="row">
        <label>Yang Berhasil
            <textarea name="what_worked">{{ old('what_worked', $review->what_worked ?? '') }}</textarea>
        </label>
        <label>Yang Gagal
            <textarea name="what_failed">{{ old('what_failed', $review->what_failed ?? '') }}</textarea>
        </label>
    </div>

    <label>Saran Pembaruan Pengetahuan GPT
        <textarea name="suggested_knowledge_update">{{ old('suggested_knowledge_update', $review->suggested_knowledge_update ?? '') }}</textarea>
    </label>
</div>

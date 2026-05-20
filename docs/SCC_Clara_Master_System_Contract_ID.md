# SCC x Clara Master System Contract (Bahasa Indonesia)

## Tujuan Dokumen
Dokumen ini menetapkan batas tanggung jawab teknis antara:

- **SCC (Sales Command Center)** sebagai **system of record** + **system of action**
- **Clara** sebagai **intelligence engine** + **copilot layer**

Dokumen ini dibuat untuk mencegah kebingungan arsitektur, duplikasi fitur, dan konflik kepemilikan sistem saat integrasi berlangsung.

---

## Part 1. GAP Analysis

| Fitur | SCC | Clara | Konflik? | Owner Final |
|---|---|---|---|---|
| Master data Lead | Ya (utama) | Parsial (shadow profile) | Ya | SCC |
| Queue eksekusi | Ya | Task suggestion | Ya | SCC |
| Timeline audit trail | Ya | Log analisis AI | Sedang | SCC (kanonik) |
| Identity pelanggan lintas channel | Dasar (lead-level) | Kuat (unified identity) | Ya | Clara (intel), SCC (truth lead) |
| Follow-up workflow + SLA | Ya | Rekomendasi | Ya | SCC |
| Assignment owner/team | Ya | Dapat menyarankan | Ya | SCC |
| Priority score operasional | Ya (rule-based) | Ya (AI score) | Ya | SCC (final), Clara (advisory) |
| Ghost risk | Dasar | Lanjutan berbasis pola | Rendah | Clara hitung, SCC konsumsi |
| Lead temperature | Dasar/manual | AI-native | Rendah | Clara hitung, SCC simpan insight |
| Objection extraction | Manual | AI-native | Rendah | Clara hitung, SCC simpan insight |
| Reply suggestion | Terbatas | Ya | Tidak | Clara |
| Approval queue AI | Tidak | Ya | Tidak | Clara |
| KPI operasional harian | Ya | Ya (insight KPI) | Sedang | SCC untuk operasional, Clara untuk intelligence KPI |
| Multi-channel ingestion | WA fokus | Multi-channel | Tidak | Clara |

### Inti hasil gap
- Konflik utama ada di **score**, **queue/task**, dan **identity ownership**.
- Solusi: **SCC mengeksekusi dan menyimpan kebenaran**, **Clara memberi saran dan analisis**.

---

## Part 2. Rencana Arsitektur Gabungan

## 2.1 Modul yang tetap di SCC
- Lead management (CRUD + status + owner)
- Queue page + queue lifecycle (`done`, `snooze`, `dismiss`)
- Timeline event (audit trail)
- Follow-up workflow & SLA
- Operational snapshot + action center
- WhatsApp execution integration
- Dashboard operasional

## 2.2 Modul yang tetap di Clara
- Ingestion & normalisasi percakapan multi-channel
- AI analysis (temperature, objection, emotion, ghost risk)
- Next action recommendation
- Marketing/behavior insights
- Model orchestration + approval logic AI

## 2.3 Titik komunikasi antar sistem
- **SCC -> Clara:** kirim event dan konteks operasional secara async
- **Clara -> SCC:** kirim hasil insight/score/rekomendasi lewat callback
- **SCC UI:** menampilkan insight Clara sebagai *advisory*, bukan perintah eksekusi otomatis

## 2.4 Visual flow
`Ads -> WhatsApp -> SCC (Lead/Queue/Timeline) -> API/Event -> Clara (AI Analysis) -> Callback -> SCC Insight -> Queue Execution oleh Sales`

---

## Part 3. API Integration Map

## 3.1 Endpoint SCC -> Clara

### POST `/api/clara/analyze-lead`
**Purpose:** analisis konteks lead + snapshot operasional.

Contoh request:
```json
{
  "event_id": "evt_20260520_001",
  "occurred_at": "2026-05-20T09:00:00+07:00",
  "lead": {
    "id": 42,
    "code": "PR-20260520-0007",
    "status": "tindak_lanjut",
    "owner_id": 12,
    "source": "iklan",
    "account_category": "mini"
  },
  "snapshot": {
    "priority_score": 74,
    "overdue_minutes": 90,
    "response_delay_minutes": 28
  }
}
```

Contoh response:
```json
{
  "accepted": true,
  "analysis_id": "anl_9001",
  "eta_seconds": 20
}
```

### POST `/api/clara/analyze-chat`
**Purpose:** analisis percakapan terbaru.

Contoh request:
```json
{
  "event_id": "evt_20260520_002",
  "lead_id": 42,
  "channel": "whatsapp",
  "messages": [
    {"direction": "inbound", "text": "masih ragu", "at": "2026-05-20T09:02:00+07:00"},
    {"direction": "outbound", "text": "boleh saya jelaskan", "at": "2026-05-20T09:03:00+07:00"}
  ]
}
```

### POST `/api/clara/analyze-followup`
**Purpose:** analisis risiko follow-up overdue dan strategi recovery.

Contoh request:
```json
{
  "event_id": "evt_20260520_003",
  "lead_id": 42,
  "followup": {
    "state": "overdue",
    "scheduled_at": "2026-05-20T08:30:00+07:00",
    "owner_id": 12
  }
}
```

## 3.2 Endpoint Clara -> SCC

### POST `/api/scc/callback`
**Purpose:** Clara mengirim hasil analisis AI ke SCC.

Contoh request:
```json
{
  "analysis_id": "anl_9001",
  "lead_id": 42,
  "generated_at": "2026-05-20T09:03:22+07:00",
  "confidence": 0.84,
  "signals": {
    "lead_score_ai": 81,
    "temperature_ai": "warm",
    "ghost_risk_ai": 67,
    "emotion_ai": "ragu",
    "objection_top": "takut_risiko"
  },
  "recommendation": {
    "code": "send_social_proof",
    "text": "Kirim bukti studi kasus mini account",
    "expires_at": "2026-05-20T13:03:22+07:00"
  }
}
```

Contoh response SCC:
```json
{
  "accepted": true,
  "stored": true
}
```

### GET `/api/scc/lead-insight/{id}`
**Purpose:** mengambil insight terbaru untuk ditampilkan di UI SCC.

Contoh response:
```json
{
  "lead_id": 42,
  "stale": false,
  "insight": {
    "lead_score_ai": 81,
    "temperature_ai": "warm",
    "ghost_risk_ai": 67,
    "recommendation_code": "send_social_proof",
    "confidence": 0.84
  }
}
```

---

## Part 4. Event Trigger Map

| Event | SCC Call Clara? | Alasan | Cooldown | Prioritas |
|---|---|---|---|---|
| `lead.created` | YES | baseline profiling awal | 10 menit/lead | Medium |
| `message.received` | YES | sinyal intent/emotion paling kuat | 2 menit/lead | High |
| `followup.overdue` | YES | butuh strategi recovery | 15 menit/lead | Medium |
| `ownership.changed` | NO | perubahan organisasi, bukan behavior signal | - | Low |
| `queue.done` | NO (default) | event eksekusi manusia; cukup untuk feedback batch | batch per jam (opsional) | Low |

### Aturan trigger
- Gunakan dedupe key: `(lead_id + event_type + cooldown_window)`
- Seluruh panggilan ke Clara harus async (queue/job), bukan synchronous request di flow user.

---

## Part 5. Anti-Chaos Rules

1. **SCC adalah pemilik kebenaran operasional.**
2. Clara tidak boleh langsung mengubah:
   - owner
   - status lead
   - assignment
   - follow-up date
   - state queue lifecycle
3. Insight AI wajib punya `expires_at` dan status `stale`.
4. Override manual oleh user selalu menang.
5. Callback Clara harus idempotent (`analysis_id` unik).
6. Jika Clara gagal/down, SCC tetap harus fully operational.
7. Timeline SCC tetap sumber audit utama.
8. Jangan pernah membuat queue eksekusi kedua di Clara.

---

## Part 6. Future-Safe Evolution (2 Tahun)

## 6.1 Jika ada channel baru (Voice, Instagram, Telegram)
- Channel ingestion masuk ke Clara (normalisasi + intelligence)
- SCC tetap menerima insight terstandardisasi per lead
- SCC hanya mengeksekusi workflow yang disepakati

## 6.2 Jika ada multi-model AI
- Model orchestration tetap di Clara
- SCC tidak boleh terikat ke vendor/model tertentu
- Contract tetap stabil via schema versioning

## 6.3 Jika ada multi-organization/tenant
- Semua event/payload wajib tenant-aware
- Isolasi data ketat di kedua sistem
- Callback wajib validasi tenant context

## 6.4 Jika traffic naik besar
- Tambah integration gateway (auth, signature, rate limit, replay protection)
- Tambah DLQ/retry policy untuk event gagal
- Tambah observability:
  - trace_id end-to-end
  - callback latency
  - stale insight ratio
  - drop/retry metrics

## 6.5 Guardrail anti-duplikasi
- Tidak ada duplikasi ownership logic
- Tidak ada duplikasi status engine
- Tidak ada duplikasi queue eksekusi
- Clara fokus intelligence, SCC fokus action

---

## Kesimpulan Arsitektur

- **SCC = System of Record + System of Action**
- **Clara = Intelligence Engine + Copilot Layer**

Jika batas ini dilanggar, risiko utamanya:
- konflik state antar sistem
- trust user turun
- tim kembali ke WhatsApp/spreadsheet manual

Dengan kontrak ini, dua sistem bisa terasa sebagai **satu pengalaman pengguna**, tanpa mengorbankan modularitas arsitektur.


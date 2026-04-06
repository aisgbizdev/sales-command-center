<?php

namespace Database\Seeders;

use App\Models\ChatReview;
use App\Models\KnowledgeUpdateQueue;
use App\Models\ManagerReviewNote;
use App\Models\Prospect;
use App\Models\ProspectLog;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $unitWest = Unit::create([
            'name' => 'Unit Barat',
            'code' => 'UNIT-BARAT',
        ]);

        $unitEast = Unit::create([
            'name' => 'Unit Timur',
            'code' => 'UNIT-TIMUR',
        ]);

        $teamAlpha = Team::create([
            'name' => 'Tim Alpha',
            'code' => 'TEAM-ALPHA',
            'unit_id' => $unitWest->id,
        ]);

        $teamBeta = Team::create([
            'name' => 'Tim Beta',
            'code' => 'TEAM-BETA',
            'unit_id' => $unitWest->id,
        ]);

        $teamGamma = Team::create([
            'name' => 'Tim Gamma',
            'code' => 'TEAM-GAMMA',
            'unit_id' => $unitEast->id,
        ]);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin SGB',
            'email' => 'admin@sgbcc.test',
            'role' => User::ROLE_SUPER_ADMIN,
            'password' => 'password123',
        ]);

        $kepalaWest = User::factory()->create([
            'name' => 'Kepala Unit Barat',
            'email' => 'kepala.barat@sgbcc.test',
            'role' => User::ROLE_KEPALA,
            'unit_id' => $unitWest->id,
            'password' => 'password123',
        ]);

        $managerAlpha = User::factory()->create([
            'name' => 'Manajer Alpha',
            'email' => 'manager.alpha@sgbcc.test',
            'role' => User::ROLE_MANAGER,
            'unit_id' => $unitWest->id,
            'team_id' => $teamAlpha->id,
            'password' => 'password123',
        ]);

        $salesOne = User::factory()->create([
            'name' => 'Sales Andi',
            'email' => 'andi@sgbcc.test',
            'role' => User::ROLE_PENJUALAN,
            'unit_id' => $unitWest->id,
            'team_id' => $teamAlpha->id,
            'password' => 'password123',
        ]);

        $salesTwo = User::factory()->create([
            'name' => 'Sales Rina',
            'email' => 'rina@sgbcc.test',
            'role' => User::ROLE_PENJUALAN,
            'unit_id' => $unitWest->id,
            'team_id' => $teamBeta->id,
            'password' => 'password123',
        ]);

        $salesThree = User::factory()->create([
            'name' => 'Sales Bayu',
            'email' => 'bayu@sgbcc.test',
            'role' => User::ROLE_PENJUALAN,
            'unit_id' => $unitEast->id,
            'team_id' => $teamGamma->id,
            'password' => 'password123',
        ]);

        $p1 = Prospect::create([
            'prospect_code' => 'PR-'.now()->format('Ymd').'-0001',
            'name' => 'Budi Santoso',
            'company' => 'PT Maju Jaya',
            'phone' => '081111111111',
            'email' => 'budi@majujaya.co.id',
            'source' => 'referensi',
            'account_category' => 'reguler',
            'status' => Prospect::STATUS_DIHUBUNGI,
            'priority' => 1,
            'estimation_value' => 25000000,
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'notes' => 'Pengambil keputusan bagian purchasing.',
            'unit_id' => $salesOne->unit_id,
            'team_id' => $salesOne->team_id,
            'owner_id' => $salesOne->id,
        ]);

        $p2 = Prospect::create([
            'prospect_code' => 'PR-'.now()->format('Ymd').'-0002',
            'name' => 'Rizky Maulana',
            'company' => 'CV Sinar Baru',
            'phone' => '082222222222',
            'email' => 'rizky@sinarbaru.co.id',
            'source' => 'iklan',
            'account_category' => 'mini',
            'status' => Prospect::STATUS_BARU,
            'priority' => 2,
            'estimation_value' => 12000000,
            'next_follow_up_date' => now()->addDays(2)->toDateString(),
            'notes' => 'Perlu presentasi produk minggu ini.',
            'unit_id' => $salesTwo->unit_id,
            'team_id' => $salesTwo->team_id,
            'owner_id' => $salesTwo->id,
        ]);

        $p3 = Prospect::create([
            'prospect_code' => 'PR-'.now()->format('Ymd').'-0003',
            'name' => 'Dian Pratama',
            'company' => 'PT Timur Sejahtera',
            'phone' => '083333333333',
            'email' => 'dian@timursejahtera.co.id',
            'source' => 'walkin',
            'account_category' => 'reguler',
            'status' => Prospect::STATUS_SEDANG_BERJALAN,
            'priority' => 1,
            'estimation_value' => 33000000,
            'next_follow_up_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Diskusi negosiasi harga final.',
            'unit_id' => $salesThree->unit_id,
            'team_id' => $salesThree->team_id,
            'owner_id' => $salesThree->id,
        ]);

        ProspectLog::create([
            'log_date' => now()->toDateString(),
            'activity_type' => 'follow_up',
            'summary' => 'Follow up penawaran awal via telepon',
            'result' => 'Prospek minta revisi kuotasi.',
            'next_follow_up_date' => now()->addDay()->toDateString(),
            'prospect_id' => $p1->id,
            'user_id' => $salesOne->id,
        ]);

        ProspectLog::create([
            'log_date' => now()->toDateString(),
            'activity_type' => 'call',
            'summary' => 'Intro call kebutuhan sistem',
            'result' => 'Prospek tertarik, jadwal demo 2 hari lagi.',
            'next_follow_up_date' => now()->addDays(2)->toDateString(),
            'prospect_id' => $p2->id,
            'user_id' => $salesTwo->id,
        ]);

        ProspectLog::create([
            'log_date' => now()->subDay()->toDateString(),
            'activity_type' => 'presentation',
            'summary' => 'Presentasi proposal ke tim procurement',
            'result' => 'Masuk tahap negosiasi kontrak.',
            'next_follow_up_date' => now()->addDays(3)->toDateString(),
            'prospect_id' => $p3->id,
            'user_id' => $salesThree->id,
        ]);

        $review1 = ChatReview::create([
            'title' => 'Closing cepat setelah handling objection harga',
            'channel' => 'whatsapp',
            'customer_name' => 'Budi Santoso',
            'customer_company' => 'PT Maju Jaya',
            'outcome' => 'berhasil',
            'status' => 'approved',
            'chat_summary' => 'Prospek awalnya keberatan harga, lalu deal setelah breakdown value.',
            'chat_excerpt' => 'Customer: kalau harga segini terlalu tinggi... Sales: kami pecah manfaat per fase...',
            'what_worked' => 'Menjawab objection dengan contoh ROI dan timeline implementasi.',
            'what_failed' => null,
            'suggested_knowledge_update' => 'Tambahkan template jawaban objection harga berbasis ROI.',
            'prospect_id' => $p1->id,
            'submitted_by' => $salesOne->id,
        ]);

        $review2 = ChatReview::create([
            'title' => 'Prospek hilang karena follow-up lambat',
            'channel' => 'telepon',
            'customer_name' => 'Rizky Maulana',
            'customer_company' => 'CV Sinar Baru',
            'outcome' => 'gagal',
            'status' => 'queued_for_approval',
            'chat_summary' => 'Prospek berpindah vendor karena respons follow-up terlambat.',
            'chat_excerpt' => 'Customer: kami sudah putuskan vendor lain.',
            'what_worked' => null,
            'what_failed' => 'Tidak ada reminder follow-up prioritas tinggi.',
            'suggested_knowledge_update' => 'Buat aturan reminder follow-up prioritas.',
            'prospect_id' => $p2->id,
            'submitted_by' => $salesTwo->id,
        ]);

        ManagerReviewNote::create([
            'note' => 'Kasus berhasil ini bisa dijadikan playbook objection handling.',
            'tag' => 'win_pattern',
            'chat_review_id' => $review1->id,
            'reviewed_by' => $managerAlpha->id,
        ]);

        ManagerReviewNote::create([
            'note' => 'Kasus gagal akibat SLA follow-up. Wajib masuk pembelajaran tim.',
            'tag' => 'loss_pattern',
            'chat_review_id' => $review2->id,
            'reviewed_by' => $kepalaWest->id,
        ]);

        KnowledgeUpdateQueue::create([
            'priority' => 'high',
            'status' => 'approved',
            'problem_pattern' => 'Objection harga berulang di tahap negosiasi.',
            'recommended_update' => 'Tambahkan script jawaban objection harga + ROI example.',
            'expected_impact' => 'Meningkatkan rasio closing prospek negosiasi.',
            'super_admin_note' => 'Setujui untuk dimasukkan ke knowledge GPT versi berikutnya.',
            'reviewed_at' => now(),
            'chat_review_id' => $review1->id,
            'requested_by' => $managerAlpha->id,
            'reviewed_by' => $superAdmin->id,
        ]);

        KnowledgeUpdateQueue::create([
            'priority' => 'urgent',
            'status' => 'queued',
            'problem_pattern' => 'Prospek hilang karena tidak ada follow-up tepat waktu.',
            'recommended_update' => 'Tambahkan reminder follow-up dan escalation rule pada prompt GPT.',
            'expected_impact' => 'Menurunkan jumlah prospek lost karena keterlambatan respons.',
            'chat_review_id' => $review2->id,
            'requested_by' => $kepalaWest->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\KnowledgeUpdateQueue;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_sales_only_sees_own_prospects_and_cannot_access_performance_or_knowledge_queue(): void
    {
        $sales = User::where('email', 'andi@sgbcc.test')->firstOrFail();

        $this->actingAs($sales)
            ->getJson('/react-api/prospects')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($sales)
            ->get('/kinerja-penjualan')
            ->assertForbidden();

        $this->actingAs($sales)
            ->get('/knowledge-queue')
            ->assertForbidden();
    }

    public function test_manager_can_update_team_prospect_but_not_other_team_prospect(): void
    {
        $manager = User::where('email', 'manager.alpha@sgbcc.test')->firstOrFail();
        $sameTeamProspect = Prospect::whereHas('owner', fn ($q) => $q->where('email', 'andi@sgbcc.test'))->firstOrFail();
        $otherTeamProspect = Prospect::whereHas('owner', fn ($q) => $q->where('email', 'rina@sgbcc.test'))->firstOrFail();

        $this->actingAs($manager)
            ->patchJson(route('prospects.quick-update', $sameTeamProspect), [
                'status' => 'tindak_lanjut',
                'quick_note' => 'Manager follow up team.',
            ])
            ->assertOk();

        $this->actingAs($manager)
            ->patchJson(route('prospects.quick-update', $otherTeamProspect), [
                'status' => 'tindak_lanjut',
                'quick_note' => 'Should fail.',
            ])
            ->assertForbidden();
    }

    public function test_kepala_can_view_knowledge_queue_but_cannot_approve(): void
    {
        $kepala = User::where('email', 'kepala.barat@sgbcc.test')->firstOrFail();
        $queue = KnowledgeUpdateQueue::firstOrFail();

        $this->actingAs($kepala)
            ->get('/knowledge-queue')
            ->assertOk();

        $this->actingAs($kepala)
            ->patch(route('knowledge-queue.approve', $queue), [
                'super_admin_note' => 'Tidak boleh approve.',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_access_all_and_delete_any_prospect(): void
    {
        $admin = User::where('email', 'admin@sgbcc.test')->firstOrFail();
        $prospect = Prospect::firstOrFail();

        $this->actingAs($admin)
            ->get('/kinerja-penjualan')
            ->assertOk();

        $this->actingAs($admin)
            ->delete(route('prospects.destroy', $prospect))
            ->assertRedirect(route('prospects.index'));

        $this->assertDatabaseMissing('prospects', [
            'id' => $prospect->id,
        ]);
    }
}

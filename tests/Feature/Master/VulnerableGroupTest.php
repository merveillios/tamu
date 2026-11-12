<?php

namespace Tests\Feature\Master;

use App\Enums\UserRole;
use App\Models\GuestVisit;
use App\Models\VulnerableGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\Concerns\InteractsWithGuestPages;
use Tests\TestCase;

/**
 * Menguji master kelompok rentan (VUL-1 sampai VUL-3) dan pemakaiannya di formulir tamu dan antrean.
 */
class VulnerableGroupTest extends TestCase
{
    use CreatesSitamuData;
    use InteractsWithGuestPages;
    use RefreshDatabase;

    public function test_migration_seeds_old_enum_codes_with_queue_priority(): void
    {
        $this->assertSame(
            ['bukan', 'lansia', 'disabilitas', 'ibu_hamil', 'tidak_menyebutkan'],
            VulnerableGroup::query()->ordered()->pluck('code')->all(),
        );
        $this->assertSame(
            ['lansia' => 1, 'disabilitas' => 2, 'ibu_hamil' => 3],
            VulnerableGroup::query()->whereNotNull('priority')->pluck('priority', 'code')->all(),
        );
    }

    public function test_admin_adds_group_with_generated_code(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post('/admin/kelompok-rentan/tambah', ['name' => '  Anak-anak  dan Balita ', 'priority' => '4', 'sort_order' => '60'])
            ->assertRedirect(route('admin.vulnerable-groups.index'));

        $group = VulnerableGroup::where('name', 'Anak-anak dan Balita')->firstOrFail();
        $this->assertSame(['anak_anak_dan_balita', 4, 60, true], [$group->code, $group->priority, $group->sort_order, $group->is_active]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vulnerable_group.create', 'entity_id' => $group->id]);

        $this->actingAs($admin)->post('/admin/kelompok-rentan/tambah', ['name' => 'anak-anak dan balita'])
            ->assertSessionHasErrors(['name' => 'Kelompok rentan dengan nama ini sudah ada.']);
    }

    public function test_update_keeps_code_and_empty_priority_means_not_prioritised(): void
    {
        $group = VulnerableGroup::where('code', 'lansia')->firstOrFail();

        $this->actingAs($this->createUser(UserRole::SuperAdmin))
            ->post("/admin/kelompok-rentan/{$group->id}/ubah", ['name' => 'Lanjut usia (60+)', 'priority' => '', 'sort_order' => '20'])
            ->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertSame(['lansia', 'Lanjut usia (60+)', null], [$group->code, $group->name, $group->priority]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vulnerable_group.update', 'entity_id' => $group->id]);
    }

    public function test_used_group_can_only_be_deactivated(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['vulnerable_group' => 'lansia']);
        $group = VulnerableGroup::where('code', 'lansia')->firstOrFail();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin/kelompok-rentan')->assertOk()->assertSee('1 kunjungan');
        $this->actingAs($admin)->post("/admin/kelompok-rentan/{$group->id}/hapus")
            ->assertSessionHasErrors('vulnerable_group');
        $this->assertModelExists($group);

        $this->actingAs($admin)->post("/admin/kelompok-rentan/{$group->id}/status");
        $this->assertFalse($group->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vulnerable_group.deactivate', 'entity_id' => $group->id]);
    }

    public function test_unused_group_can_be_deleted(): void
    {
        $group = VulnerableGroup::where('code', 'ibu_hamil')->firstOrFail();

        $this->actingAs($this->createAdmin())->post("/admin/kelompok-rentan/{$group->id}/hapus")
            ->assertRedirect(route('admin.vulnerable-groups.index'));

        $this->assertModelMissing($group);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vulnerable_group.delete', 'entity_id' => $group->id]);
    }

    public function test_last_active_group_cannot_be_deactivated_or_deleted(): void
    {
        VulnerableGroup::where('code', '!=', 'bukan')->update(['is_active' => false]);
        $last = VulnerableGroup::where('code', 'bukan')->firstOrFail();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post("/admin/kelompok-rentan/{$last->id}/status")->assertSessionHasErrors('vulnerable_group');
        $this->actingAs($admin)->post("/admin/kelompok-rentan/{$last->id}/hapus")->assertSessionHasErrors('vulnerable_group');

        $this->assertTrue($last->refresh()->is_active);
    }

    public function test_inactive_group_is_hidden_from_new_guest_and_rejected(): void
    {
        [$qr] = $this->usableQr();
        VulnerableGroup::where('code', 'ibu_hamil')->update(['is_active' => false]);
        $this->verifiedAs($qr, 'baru@contoh.test');

        $this->get("/tamu/{$qr->token}/formulir")->assertOk()->assertSee('Lansia')->assertDontSee('Ibu hamil');
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload(session("guest.{$qr->id}.form_token"), ['vulnerable_group' => 'ibu_hamil']))
            ->assertSessionHasErrors(['vulnerable_group' => 'Pilih kelompok rentan.']);
        $this->assertSame(0, GuestVisit::count());
    }

    public function test_returning_guest_keeps_group_that_was_deactivated(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['email' => 'lama@contoh.test', 'vulnerable_group' => 'ibu_hamil']);
        VulnerableGroup::where('code', 'ibu_hamil')->update(['is_active' => false]);
        $this->verifiedAs($qr, 'lama@contoh.test');

        $this->get("/tamu/{$qr->token}/formulir")->assertOk()->assertSee('Ibu hamil');
        $this->post("/tamu/{$qr->token}/formulir", $this->guestPayload(session("guest.{$qr->id}.form_token")))
            ->assertStatus(303);

        $this->assertSame('ibu_hamil', GuestVisit::latest('id')->firstOrFail()->vulnerable_group);
    }

    public function test_queue_priority_follows_master_data(): void
    {
        [$qr, $category] = $this->usableQr();
        $this->createVisit($qr, $category, ['name' => 'Tamu Biasa', 'vulnerable_group' => 'bukan', 'check_in_at' => now()->subMinutes(30)]);
        $this->createVisit($qr, $category, ['name' => 'Tamu Lansia', 'vulnerable_group' => 'lansia', 'check_in_at' => now()->subMinutes(10)]);
        $this->createVisit($qr, $category, ['name' => 'Tamu Hamil', 'vulnerable_group' => 'ibu_hamil', 'check_in_at' => now()->subMinutes(5)]);
        $admin = $this->createAdmin();

        $names = fn () => collect($this->actingAs($admin)->getJson('/admin/tamu/data')->json('data.rows'))->pluck('name')->all();
        $this->assertSame(['Tamu Lansia', 'Tamu Hamil', 'Tamu Biasa'], $names());

        VulnerableGroup::where('code', 'ibu_hamil')->update(['priority' => 1]);
        VulnerableGroup::where('code', 'lansia')->update(['priority' => null]);
        $this->assertSame(['Tamu Hamil', 'Tamu Biasa', 'Tamu Lansia'], $names());

        $rows = collect($this->actingAs($admin)->getJson('/admin/tamu/data')->json('data.rows'));
        $this->assertSame('Ibu hamil', $rows->firstWhere('name', 'Tamu Hamil')['vulnerable_group']);
    }

    public function test_receptionist_and_pic_cannot_manage_groups(): void
    {
        $department = $this->createDepartment($this->createLocation());

        $this->actingAs($this->createUser(UserRole::Receptionist))->get('/admin/kelompok-rentan')->assertForbidden();
        $this->actingAs($this->createPic($department))->post('/admin/kelompok-rentan/tambah', ['name' => 'Coba'])->assertForbidden();
        $this->assertDatabaseMissing('vulnerable_groups', ['name' => 'Coba']);
    }
}

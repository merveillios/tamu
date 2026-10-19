<?php

namespace Tests\Feature\Master;

use App\Enums\UserRole;
use App\Models\PurposeCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSitamuData;
use Tests\TestCase;

/**
 * Menguji kategori keperluan (KAT-1 sampai KAT-3).
 */
class PurposeCategoryTest extends TestCase
{
    use CreatesSitamuData;
    use RefreshDatabase;

    public function test_admin_manages_categories_in_unassigned_location(): void
    {
        $assigned = $this->createLocation();
        $other = $this->createLocation();
        $department = $this->createDepartment($other);
        $admin = $this->createAdmin($assigned);

        $this->actingAs($admin)
            ->post("/admin/kategori-keperluan/tambah?location_id={$other->id}", [
                'name' => 'Konsultasi',
                'suggested_department_id' => $department->id,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.categories.index', ['location_id' => $other->id, 'departemen' => $department->id]));

        $category = PurposeCategory::firstOrFail();
        $this->assertSame([$other->id, $department->id, 2], [$category->location_id, $category->suggested_department_id, $category->sort_order]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'purpose_category.create', 'entity_id' => $category->id]);
    }

    public function test_suggested_department_must_be_active_and_in_same_location(): void
    {
        $location = $this->createLocation();
        $otherLocationDepartment = $this->createDepartment($this->createLocation());
        $inactiveDepartment = $this->createDepartment($location);
        $inactiveDepartment->update(['is_active' => false]);
        $admin = $this->createAdmin($location);

        foreach ([$otherLocationDepartment, $inactiveDepartment] as $department) {
            $this->actingAs($admin)
                ->post("/admin/kategori-keperluan/tambah?location_id={$location->id}", ['name' => 'Kategori '.$department->id, 'suggested_department_id' => $department->id])
                ->assertSessionHasErrors('suggested_department_id');
        }

        $this->assertDatabaseCount('purpose_categories', 0);
    }

    public function test_name_is_unique_per_location(): void
    {
        $location = $this->createLocation();
        $other = $this->createLocation();
        $admin = $this->createAdmin($location);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Konsultasi']);

        $this->actingAs($admin)->post("/admin/kategori-keperluan/tambah?location_id={$location->id}", ['name' => 'konsultasi'])
            ->assertSessionHasErrors(['name' => 'Pertanyaan ini sudah ada di lokasi ini.']);
        $this->actingAs($admin)->post("/admin/kategori-keperluan/tambah?location_id={$other->id}", ['name' => 'Konsultasi'])
            ->assertSessionHasNoErrors();
    }

    public function test_department_page_orders_by_sort_order_then_name(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Zebra', 'sort_order' => 1]);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Beta', 'sort_order' => 2]);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Alfa', 'sort_order' => 2]);

        $this->actingAs($admin)->get("/admin/kategori-keperluan?location_id={$location->id}&departemen=umum")
            ->assertOk()
            ->assertSeeInOrder(['Zebra', 'Alfa', 'Beta']);
    }

    public function test_index_lists_departments_without_their_questions(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);
        $secretariat = $this->createDepartment($location, 'Sekretariat');
        $industry = $this->createDepartment($location, 'Bidang Industri Agro');
        $this->createDepartment($location, 'Bidang Kosong');
        $inactive = $this->createDepartment($location, 'Bidang Lama');
        $inactive->update(['is_active' => false]);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Lainnya']);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Saya ingin mengantar surat', 'suggested_department_id' => $secretariat->id]);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Bagaimana cara mengurus izin usaha?', 'suggested_department_id' => $industry->id]);

        $this->actingAs($admin)->get("/admin/kategori-keperluan?location_id={$location->id}")
            ->assertOk()
            ->assertSeeInOrder(['Bidang Industri Agro', 'Bidang Kosong', 'Sekretariat', 'Umum (tanpa departemen)'])
            ->assertSee(route('admin.categories.index', ['location_id' => $location->id, 'departemen' => $industry->id]))
            ->assertSee('Lihat pertanyaan')
            ->assertDontSee('Bidang Lama')
            ->assertDontSee('Bagaimana cara mengurus izin usaha?');
    }

    public function test_department_page_shows_only_its_questions(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);
        $industry = $this->createDepartment($location, 'Bidang Industri Agro');
        $empty = $this->createDepartment($location, 'Bidang Kosong');
        $inactive = $this->createDepartment($location, 'Bidang Lama');
        $inactive->update(['is_active' => false]);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Bagaimana cara mengurus izin usaha?', 'suggested_department_id' => $industry->id]);
        PurposeCategory::create(['location_id' => $location->id, 'name' => 'Pertanyaan lama', 'suggested_department_id' => $inactive->id]);

        $this->actingAs($admin)->get("/admin/kategori-keperluan?location_id={$location->id}&departemen={$industry->id}")
            ->assertOk()
            ->assertSee('Bagaimana cara mengurus izin usaha?')
            ->assertDontSee('Pertanyaan lama')
            ->assertSee(route('admin.categories.create', ['location_id' => $location->id, 'departemen' => $industry->id]));

        $this->actingAs($admin)->get("/admin/kategori-keperluan?location_id={$location->id}&departemen={$empty->id}")
            ->assertOk()
            ->assertSee('Belum ada pertanyaan di departemen ini.');

        $this->actingAs($admin)->get("/admin/kategori-keperluan?location_id={$location->id}&departemen=umum")
            ->assertOk()
            ->assertSeeInOrder(['Umum (tanpa departemen)', 'Pertanyaan lama', 'Departemen Bidang Lama nonaktif'])
            ->assertDontSee('Bagaimana cara mengurus izin usaha?');
    }

    public function test_create_prefills_department_and_saving_returns_to_its_section(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);
        $department = $this->createDepartment($location);

        $this->actingAs($admin)->get("/admin/kategori-keperluan/tambah?location_id={$location->id}&departemen={$department->id}")
            ->assertOk()
            ->assertSee("value=\"{$department->id}\" selected", false);

        $this->actingAs($admin)
            ->post("/admin/kategori-keperluan/tambah?location_id={$location->id}", ['name' => 'Bagaimana cara ikut pelatihan?', 'suggested_department_id' => $department->id])
            ->assertRedirect(route('admin.categories.index', ['location_id' => $location->id, 'departemen' => $department->id]));

        $category = PurposeCategory::where('name', 'Bagaimana cara ikut pelatihan?')->firstOrFail();
        $this->actingAs($admin)->post("/admin/kategori-keperluan/{$category->id}/ubah", ['name' => $category->name])
            ->assertRedirect(route('admin.categories.index', ['location_id' => $location->id, 'departemen' => 'umum']));
        $this->actingAs($admin)->post("/admin/kategori-keperluan/{$category->id}/status")
            ->assertRedirect(route('admin.categories.index', ['location_id' => $location->id, 'departemen' => 'umum']));
    }

    public function test_question_accepts_up_to_150_characters(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);
        $url = "/admin/kategori-keperluan/tambah?location_id={$location->id}";

        $this->actingAs($admin)->post($url, ['name' => str_repeat('a', 150)])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['name' => str_repeat('b', 151)])
            ->assertSessionHasErrors(['name' => 'Pertanyaan wajib diisi (2–150 karakter).']);
        $this->assertDatabaseHas('purpose_categories', ['name' => str_repeat('a', 150)]);
    }

    public function test_updates_and_toggles_category(): void
    {
        $location = $this->createLocation();
        $admin = $this->createAdmin($location);
        $category = PurposeCategory::create(['location_id' => $location->id, 'name' => 'Lama']);

        $this->actingAs($admin)->post("/admin/kategori-keperluan/{$category->id}/ubah", ['name' => 'Baru', 'sort_order' => 5])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/admin/kategori-keperluan/{$category->id}/status");

        $category->refresh();
        $this->assertSame(['Baru', 5, false], [$category->name, $category->sort_order, $category->is_active]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'purpose_category.deactivate', 'entity_id' => $category->id]);
    }

    public function test_receptionist_and_pic_cannot_manage_categories(): void
    {
        $location = $this->createLocation();
        $department = $this->createDepartment($location);

        foreach ([$this->createUser(UserRole::Receptionist), $this->createPic($department)] as $user) {
            $this->flushSession();
            $this->actingAs($user)->get('/admin/kategori-keperluan')->assertForbidden();
        }
    }
}

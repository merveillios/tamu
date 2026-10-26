<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji halaman ber-layout Volt dan menu staf per peran (Bab 13.2).
 */
class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_login_pages_render_with_local_assets(): void
    {
        $this->get('/')->assertOk()->assertSee('assets/vendor/volt/css/volt.css')->assertSee('Selamat Datang');
        $this->get('/login')->assertOk()->assertSee('name="email"', false)->assertSee('name="password"', false);
    }

    public function test_guest_is_redirected_from_staff_panel_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_custom_404_page_is_in_indonesian(): void
    {
        $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_receptionist_menu(): void
    {
        $this->assertMenu(UserRole::Receptionist, ['Tamu Aktif', 'Ganti Password'], ['Dashboard', 'Penugasan', 'History', 'Pengguna']);
    }

    public function test_pic_menu(): void
    {
        $this->assertMenu(UserRole::Pic, ['Penugasan', 'Ganti Password'], ['Dashboard', 'Tamu Aktif', 'History', 'Pengguna']);
    }

    public function test_admin_menu(): void
    {
        $this->assertMenu(
            UserRole::Admin,
            ['Dashboard', 'Tamu Aktif', 'History', 'Lokasi', 'QR Code', 'Pertanyaan', 'Petugas', 'Pengguna', 'Audit Log', 'Ganti Password'],
            ['Penugasan'],
        );
    }

    public function test_super_admin_menu(): void
    {
        $this->assertMenu(
            UserRole::SuperAdmin,
            ['Dashboard', 'Tamu Aktif', 'History', 'Lokasi', 'Pertanyaan', 'Petugas', 'Pengguna', 'Audit Log', 'Ganti Password'],
            ['Penugasan'],
        );
    }

    /**
     * Memastikan sidebar peran tertentu memuat dan tidak memuat menu yang diberikan.
     *
     * @param  array<int, string>  $visible
     * @param  array<int, string>  $hidden
     */
    private function assertMenu(UserRole $role, array $visible, array $hidden): void
    {
        $html = $this->actingAs(User::factory()->role($role)->create())
            ->get('/admin/password')
            ->assertOk()
            ->getContent();

        preg_match('/<nav id="sidebarMenu".*?<\/nav>/s', $html, $sidebar);

        foreach ($visible as $label) {
            $this->assertStringContainsString('<span class="sidebar-text">'.$label.'</span>', $sidebar[0], "Menu {$label} harus tampil.");
        }

        foreach ($hidden as $label) {
            $this->assertStringNotContainsString('<span class="sidebar-text">'.$label.'</span>', $sidebar[0], "Menu {$label} tidak boleh tampil.");
        }
    }
}

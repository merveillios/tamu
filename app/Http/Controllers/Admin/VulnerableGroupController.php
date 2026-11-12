<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VulnerableGroupRequest;
use App\Models\GuestVisit;
use App\Models\VulnerableGroup;
use App\Services\VulnerableGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Master kelompok rentan untuk Admin dan Super Admin (VUL-1 sampai VUL-3).
 * Daftarnya berlaku untuk semua lokasi, sama seperti cakupan Admin (ADM-5).
 */
class VulnerableGroupController extends Controller
{
    /**
     * Daftar semua kelompok rentan beserta jumlah kunjungan yang memakainya.
     */
    public function index(): View
    {
        $usage = GuestVisit::query()
            ->selectRaw('vulnerable_group, COUNT(*) AS total')
            ->groupBy('vulnerable_group')
            ->pluck('total', 'vulnerable_group');

        return view('admin.vulnerable-groups.index', [
            'groups' => VulnerableGroup::query()->ordered()->get(),
            'usage' => $usage,
        ]);
    }

    /**
     * Form kelompok baru.
     */
    public function create(): View
    {
        $nextOrder = ((int) VulnerableGroup::query()->max('sort_order')) + 10;

        return view('admin.vulnerable-groups.form', ['group' => new VulnerableGroup(['sort_order' => min($nextOrder, 999)])]);
    }

    /**
     * Menyimpan kelompok baru.
     */
    public function store(VulnerableGroupRequest $request, VulnerableGroupService $groups): RedirectResponse
    {
        $group = $groups->create($request->validated(), $request->user());

        return redirect()->route('admin.vulnerable-groups.index')
            ->with('success', "Kelompok rentan \"{$group->name}\" berhasil ditambahkan.");
    }

    /**
     * Form ubah kelompok.
     */
    public function edit(int $id): View
    {
        return view('admin.vulnerable-groups.form', ['group' => VulnerableGroup::findOrFail($id)]);
    }

    /**
     * Menyimpan perubahan kelompok.
     */
    public function update(VulnerableGroupRequest $request, int $id, VulnerableGroupService $groups): RedirectResponse
    {
        $group = $groups->update(VulnerableGroup::findOrFail($id), $request->validated(), $request->user());

        return redirect()->route('admin.vulnerable-groups.index')
            ->with('success', "Kelompok rentan \"{$group->name}\" berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan kelompok (VUL-2).
     */
    public function toggleStatus(Request $request, int $id, VulnerableGroupService $groups): RedirectResponse
    {
        $group = VulnerableGroup::findOrFail($id);
        $groups->setActive($group, ! $group->is_active, $request->user());

        $message = $group->is_active ? "Kelompok rentan \"{$group->name}\" diaktifkan." : "Kelompok rentan \"{$group->name}\" dinonaktifkan.";

        return redirect()->route('admin.vulnerable-groups.index')->with('success', $message);
    }

    /**
     * Menghapus kelompok yang belum pernah dipakai kunjungan (VUL-3).
     */
    public function destroy(Request $request, int $id, VulnerableGroupService $groups): RedirectResponse
    {
        $group = VulnerableGroup::findOrFail($id);
        $groups->delete($group, $request->user());

        return redirect()->route('admin.vulnerable-groups.index')
            ->with('success', "Kelompok rentan \"{$group->name}\" dihapus.");
    }
}

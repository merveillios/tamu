<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurposeCategoryRequest;
use App\Models\Department;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Services\AccessScope;
use App\Services\PurposeCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kategori keperluan per lokasi untuk Admin dan Super Admin (KAT-1 sampai KAT-3).
 */
class PurposeCategoryController extends Controller
{
    public function __construct(private readonly AccessScope $scope)
    {
    }

    /**
     * Daftar kategori sebuah lokasi, urut sesuai urutan tampil lalu nama.
     */
    public function index(Request $request): View
    {
        $locations = $this->scope->manageableLocations($request->user())->orderBy('name')->get(['id', 'name']);
        $location = $this->selectedLocation($request, $locations->first()?->id);

        $categories = $location === null ? collect() : PurposeCategory::query()
            ->forUser($request->user())
            ->where('location_id', $location->id)
            ->with('suggestedDepartment:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', [
            'locations' => $locations,
            'location' => $location,
            'categories' => $categories,
        ]);
    }

    /**
     * Form kategori baru untuk lokasi yang dipilih.
     */
    public function create(Request $request): View
    {
        $location = $this->scope->findManageableLocationOrDeny((int) $request->query('location_id'), $request->user());

        return view('admin.categories.form', $this->formData(new PurposeCategory(), $location));
    }

    /**
     * Menyimpan kategori baru.
     */
    public function store(PurposeCategoryRequest $request, PurposeCategoryService $categories): RedirectResponse
    {
        $location = $this->scope->findManageableLocationOrDeny((int) $request->query('location_id'), $request->user());
        $category = $categories->create($location, $request->validated(), $request->user());

        return redirect()->route('admin.categories.index', ['location_id' => $location->id])
            ->with('success', "Pertanyaan \"{$category->name}\" berhasil ditambahkan.");
    }

    /**
     * Form ubah kategori.
     */
    public function edit(Request $request, int $id): View
    {
        $category = $this->scope->findOrDeny(PurposeCategory::class, $id, $request->user());

        return view('admin.categories.form', $this->formData($category, $category->location));
    }

    /**
     * Menyimpan perubahan kategori.
     */
    public function update(PurposeCategoryRequest $request, int $id, PurposeCategoryService $categories): RedirectResponse
    {
        $category = $this->scope->findOrDeny(PurposeCategory::class, $id, $request->user());
        $categories->update($category, $request->validated(), $request->user());

        return redirect()->route('admin.categories.index', ['location_id' => $category->location_id])
            ->with('success', "Pertanyaan \"{$category->name}\" berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan kategori (KAT-2).
     */
    public function toggleStatus(Request $request, int $id, PurposeCategoryService $categories): RedirectResponse
    {
        $category = $this->scope->findOrDeny(PurposeCategory::class, $id, $request->user());
        $categories->setActive($category, ! $category->is_active, $request->user());

        $message = $category->is_active ? "Pertanyaan \"{$category->name}\" diaktifkan." : "Pertanyaan \"{$category->name}\" dinonaktifkan.";

        return back()->with('success', $message);
    }

    /**
     * Lokasi terpilih dari parameter location_id, atau lokasi pertama yang dapat dikelola.
     */
    private function selectedLocation(Request $request, ?int $defaultId): ?Location
    {
        $id = $request->query('location_id', $defaultId);

        return $id === null ? null : $this->scope->findManageableLocationOrDeny($id, $request->user());
    }

    /**
     * Data form: kategori, lokasinya, dan departemen aktif di lokasi itu untuk saran.
     *
     * @return array<string, mixed>
     */
    private function formData(PurposeCategory $category, Location $location): array
    {
        return [
            'category' => $category,
            'location' => $location,
            'departments' => Department::query()
                ->where('location_id', $location->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}

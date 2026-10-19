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
use Illuminate\Support\Collection;
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
     * Daftar departemen sebuah lokasi. Dengan parameter `departemen` (ID departemen atau `umum`),
     * halaman menampilkan pertanyaan departemen itu.
     */
    public function index(Request $request): View
    {
        $locations = $this->scope->manageableLocations($request->user())->orderBy('name')->get(['id', 'name']);
        $location = $this->selectedLocation($request, $locations->first()?->id);
        $sections = $location === null ? collect() : $this->sections($request, $location);

        return view('admin.categories.index', [
            'locations' => $locations,
            'location' => $location,
            'sections' => $sections,
            'section' => $sections->firstWhere('key', (string) $request->query('departemen', '')),
        ]);
    }

    /**
     * Form pertanyaan baru; departemen terisi dari bagian tempat tombol tambah ditekan.
     */
    public function create(Request $request): View
    {
        $location = $this->scope->findManageableLocationOrDeny((int) $request->query('location_id'), $request->user());
        $category = new PurposeCategory(['suggested_department_id' => (int) $request->query('departemen') ?: null]);

        return view('admin.categories.form', $this->formData($category, $location));
    }

    /**
     * Menyimpan kategori baru.
     */
    public function store(PurposeCategoryRequest $request, PurposeCategoryService $categories): RedirectResponse
    {
        $location = $this->scope->findManageableLocationOrDeny((int) $request->query('location_id'), $request->user());
        $category = $categories->create($location, $request->validated(), $request->user());

        return redirect($this->sectionUrl($category))
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

        return redirect($this->sectionUrl($category))
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

        return redirect($this->sectionUrl($category))->with('success', $message);
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
     * Bagian halaman: satu per departemen aktif (urut nama, termasuk yang belum punya pertanyaan),
     * lalu Umum untuk pertanyaan tanpa departemen aktif. Pertanyaan urut sesuai urutan tampil lalu nama.
     *
     * @return Collection<int, array{key: string, department: ?Department, categories: Collection<int, PurposeCategory>}>
     */
    private function sections(Request $request, Location $location): Collection
    {
        $categories = PurposeCategory::query()
            ->forUser($request->user())
            ->where('location_id', $location->id)
            ->with('suggestedDepartment:id,name,is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->toBase()
            ->groupBy(fn (PurposeCategory $category) => $this->sectionKey($category));

        return Department::query()
            ->where('location_id', $location->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toBase()
            ->map(fn (Department $department) => [
                'key' => (string) $department->id,
                'department' => $department,
                'categories' => $categories->get($department->id, collect()),
            ])
            ->push(['key' => 'umum', 'department' => null, 'categories' => $categories->get('umum', collect())]);
    }

    /**
     * Kunci bagian halaman untuk pertanyaan: ID departemen aktifnya, atau `umum`.
     */
    private function sectionKey(PurposeCategory $category): string
    {
        return $category->groupName() !== null ? (string) $category->suggested_department_id : 'umum';
    }

    /**
     * Halaman daftar dengan bagian departemen pertanyaan ini terbuka.
     */
    private function sectionUrl(PurposeCategory $category): string
    {
        $category->load('suggestedDepartment:id,name,is_active');

        return route('admin.categories.index', ['location_id' => $category->location_id, 'departemen' => $this->sectionKey($category)]);
    }

    /**
     * Data form: pertanyaan, lokasinya, departemen aktif di lokasi itu, dan alamat tombol Batal.
     *
     * @return array<string, mixed>
     */
    private function formData(PurposeCategory $category, Location $location): array
    {
        return [
            'category' => $category,
            'location' => $location,
            'backUrl' => route('admin.categories.index', [
                'location_id' => $location->id,
                'departemen' => $category->exists ? $this->sectionKey($category) : ($category->suggested_department_id ?? 'umum'),
            ]),
            'departments' => Department::query()
                ->where('location_id', $location->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}

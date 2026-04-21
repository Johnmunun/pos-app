<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Shop;
use Src\Infrastructure\Ecommerce\Models\CmsBannerModel;

class CmsBannerController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;

        if ($isRoot) {
            $sessionShopId = $request->session()->get('current_storefront_shop_id');
            if ($sessionShopId && is_numeric((string) $sessionShopId) && Shop::find((int) $sessionShopId)) {
                return (string) ((int) $sessionShopId);
            }
            if (!empty($user->shop_id) && Shop::find((int) $user->shop_id)) {
                return (string) ((int) $user->shop_id);
            }
            abort(403, 'Please select a shop first.');
        }

        if (!empty($user->tenant_id)) {
            $tenantShopId = Shop::where('tenant_id', (int) $user->tenant_id)->value('id');
            if ($tenantShopId) {
                return (string) $tenantShopId;
            }
        }

        if (!empty($user->shop_id) && Shop::find((int) $user->shop_id)) {
            return (string) ((int) $user->shop_id);
        }

        abort(403, 'Shop ID not found.');
    }

    /**
     * Compat historique: certains enregistrements ont tenant_id stocké comme shop_id.
     *
     * @return list<string>
     */
    private function resolveBannerShopIds(Request $request, string $shopId): array
    {
        $ids = [$shopId];
        $user = $request->user();
        $tenantId = $user && $user->tenant_id ? (string) $user->tenant_id : null;
        if ($tenantId !== null && ctype_digit($tenantId) && $tenantId !== $shopId) {
            $ids[] = (string) ((int) $tenantId);
        }

        return array_values(array_unique($ids));
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);
        $bannerShopIds = $this->resolveBannerShopIds($request, $shopId);

        if (!\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_banners')) {
            return Inertia::render('Ecommerce/Cms/Banners/Index', ['banners' => []]);
        }

        $banners = CmsBannerModel::whereIn('shop_id', $bannerShopIds)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'image_path' => $b->image_path,
                'image_url' => $b->image_path ? (
                    str_starts_with($b->image_path, 'http://')
                    || str_starts_with($b->image_path, 'https://')
                    || str_starts_with($b->image_path, '/storage/')
                        ? $b->image_path
                        : (Storage::disk('public')->exists($b->image_path) ? Storage::url($b->image_path) : null)
                ) : null,
                'link' => $b->link,
                'position' => $b->position,
                'is_active' => $b->is_active,
                'sort_order' => $b->sort_order,
            ]);

        $positions = [
            ['value' => 'homepage', 'label' => 'Page d\'accueil'],
            ['value' => 'promotion', 'label' => 'Promotion'],
            ['value' => 'slider', 'label' => 'Slider'],
        ];

        return Inertia::render('Ecommerce/Cms/Banners/Index', [
            'banners' => $banners,
            'positions' => $positions,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->getShopId($request);
        $positions = [
            ['value' => 'homepage', 'label' => 'Page d\'accueil'],
            ['value' => 'promotion', 'label' => 'Promotion'],
            ['value' => 'slider', 'label' => 'Slider'],
        ];
        return Inertia::render('Ecommerce/Cms/Banners/Form', ['banner' => null, 'positions' => $positions]);
    }

    public function edit(Request $request, string $id): Response
    {
        $shopId = $this->getShopId($request);
        $bannerShopIds = $this->resolveBannerShopIds($request, $shopId);
        $banner = CmsBannerModel::whereIn('shop_id', $bannerShopIds)->findOrFail($id);
        $positions = [
            ['value' => 'homepage', 'label' => 'Page d\'accueil'],
            ['value' => 'promotion', 'label' => 'Promotion'],
            ['value' => 'slider', 'label' => 'Slider'],
        ];
        return Inertia::render('Ecommerce/Cms/Banners/Form', [
            'banner' => [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_path' => $banner->image_path,
                'link' => $banner->link,
                'position' => $banner->position,
                'is_active' => $banner->is_active,
            ],
            'positions' => $positions,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image_path' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|max:5120',
            'link' => 'nullable|string|max:500',
            'position' => 'required|string|in:homepage,promotion,slider',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image_file')) {
            $validated['image_path'] = $request->file('image_file')->store('ecommerce/cms/banners/'.$shopId, 'public');
        }
        unset($validated['image_file']);
        $validated['shop_id'] = $shopId;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        CmsBannerModel::create($validated);

        return redirect()->route('ecommerce.cms.banners.index')->with('success', 'Bannière créée.');
    }

    public function update(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $bannerShopIds = $this->resolveBannerShopIds($request, $shopId);

        $banner = CmsBannerModel::whereIn('shop_id', $bannerShopIds)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image_path' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|max:5120',
            'link' => 'nullable|string|max:500',
            'position' => 'required|string|in:homepage,promotion,slider',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image_file')) {
            $validated['image_path'] = $request->file('image_file')->store('ecommerce/cms/banners/'.$shopId, 'public');
        }
        unset($validated['image_file']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $banner->update($validated);

        return redirect()->route('ecommerce.cms.banners.index')->with('success', 'Bannière mise à jour.');
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);
        $bannerShopIds = $this->resolveBannerShopIds($request, $shopId);

        $banner = CmsBannerModel::whereIn('shop_id', $bannerShopIds)->findOrFail($id);
        $banner->delete();

        return redirect()->route('ecommerce.cms.banners.index')->with('success', 'Bannière supprimée.');
    }
}

<?php

namespace Src\Infrastructure\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Ecommerce\Models\CmsMediaModel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CmsMediaController
{
    private function getShopId(Request $request): string
    {
        $user = $request->user();
        if (!$user) abort(403, 'User not authenticated.');
        $shopId = $user->shop_id ?? ($user->tenant_id ? (string) $user->tenant_id : null);
        $isRoot = \App\Models\User::find($user->id)?->isRoot() ?? false;
        if (!$shopId && !$isRoot) abort(403, 'Shop ID not found.');
        if ($isRoot && !$shopId) abort(403, 'Please select a shop first.');
        return (string) $shopId;
    }

    public function index(Request $request): Response
    {
        $shopId = $this->getShopId($request);

        if (!\Illuminate\Support\Facades\Schema::hasTable('ecommerce_cms_media')) {
            return Inertia::render('Ecommerce/Cms/Media/Index', ['media' => []]);
        }

        $media = CmsMediaModel::where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'file_path' => $m->file_path,
                'url' => Storage::disk('public')->exists($m->file_path) ? Storage::disk('public')->url($m->file_path) : null,
                'file_type' => $m->file_type,
                'file_size' => $m->file_size,
                'mime_type' => $m->mime_type,
            ]);

        return Inertia::render('Ecommerce/Cms/Media/Index', ['media' => $media]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $path = $file->store('ecommerce/cms/media/' . $shopId, 'public');

        $mime = $file->getMimeType();
        $fileType = str_starts_with($mime ?? '', 'image/') ? 'image' : 'document';

        CmsMediaModel::create([
            'shop_id' => $shopId,
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $fileType,
            'file_size' => $file->getSize(),
            'mime_type' => $mime,
        ]);

        return redirect()->route('ecommerce.cms.media.index')->with('success', 'Fichier ajouté.');
    }

    public function destroy(Request $request, string $id): \Illuminate\Http\RedirectResponse
    {
        $shopId = $this->getShopId($request);

        $media = CmsMediaModel::where('shop_id', $shopId)->findOrFail($id);

        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return redirect()->route('ecommerce.cms.media.index')->with('success', 'Fichier supprimé.');
    }
}

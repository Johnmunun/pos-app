<?php

namespace Src\Infrastructure\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppBrandingController extends Controller
{
    private const DISK = 'public';
    private const PATH = 'settings/app';
    private const FILENAME = 'app-logo.png';

    public function index(Request $request): Response
    {
        $logoUrl = null;
        $fullPath = self::PATH . '/' . self::FILENAME;

        if (Storage::disk(self::DISK)->exists($fullPath)) {
            $logoUrl = Storage::disk(self::DISK)->url($fullPath);
        }

        return Inertia::render('Admin/Branding', [
            'appLogoUrl' => $logoUrl,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        $disk = Storage::disk(self::DISK);
        $fullPath = self::PATH . '/' . self::FILENAME;

        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            $disk->putFileAs(self::PATH, $file, self::FILENAME);
        } elseif ($request->boolean('remove_logo')) {
            if ($disk->exists($fullPath)) {
                $disk->delete($fullPath);
            }
        }

        return redirect()->route('admin.branding')
            ->with('success', 'Logo de l’application mis à jour avec succès.');
    }
}


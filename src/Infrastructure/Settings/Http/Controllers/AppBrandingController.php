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
    private const HERO_MAIN_FILENAME = 'hero-pos-main.png';
    private const HERO_DEVICES_FILENAME = 'hero-pos-devices.png';

    public function index(Request $request): Response
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);

        $logoUrl = null;
        $heroMainUrl = null;
        $heroDevicesUrl = null;

        $logoPath = self::PATH . '/' . self::FILENAME;
        $heroMainPath = self::PATH . '/' . self::HERO_MAIN_FILENAME;
        $heroDevicesPath = self::PATH . '/' . self::HERO_DEVICES_FILENAME;

        if ($disk->exists($logoPath)) {
            $logoUrl = $disk->url($logoPath);
        }

        if ($disk->exists($heroMainPath)) {
            $heroMainUrl = $disk->url($heroMainPath);
        }

        if ($disk->exists($heroDevicesPath)) {
            $heroDevicesUrl = $disk->url($heroDevicesPath);
        }

        return Inertia::render('Admin/Branding', [
            'appLogoUrl' => $logoUrl,
            'heroMainUrl' => $heroMainUrl,
            'heroDevicesUrl' => $heroDevicesUrl,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'hero_main' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'hero_devices' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'remove_hero_main' => 'nullable|boolean',
            'remove_hero_devices' => 'nullable|boolean',
        ]);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);
        $logoPath = self::PATH . '/' . self::FILENAME;
        $heroMainPath = self::PATH . '/' . self::HERO_MAIN_FILENAME;
        $heroDevicesPath = self::PATH . '/' . self::HERO_DEVICES_FILENAME;

        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            $disk->putFileAs(self::PATH, $file, self::FILENAME);
        } elseif ($request->boolean('remove_logo')) {
            if ($disk->exists($logoPath)) {
                $disk->delete($logoPath);
            }
        }

        if ($request->hasFile('hero_main')) {
            $file = $request->file('hero_main');
            $disk->putFileAs(self::PATH, $file, self::HERO_MAIN_FILENAME);
        } elseif ($request->boolean('remove_hero_main')) {
            if ($disk->exists($heroMainPath)) {
                $disk->delete($heroMainPath);
            }
        }

        if ($request->hasFile('hero_devices')) {
            $file = $request->file('hero_devices');
            $disk->putFileAs(self::PATH, $file, self::HERO_DEVICES_FILENAME);
        } elseif ($request->boolean('remove_hero_devices')) {
            if ($disk->exists($heroDevicesPath)) {
                $disk->delete($heroDevicesPath);
            }
        }

        return redirect()->route('admin.branding')
            ->with('success', 'Branding de l’application mis à jour avec succès.');
    }
}


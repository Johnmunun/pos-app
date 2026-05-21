<?php

namespace Src\Infrastructure\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Marketing\Services\ApplicationSeoService;

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

        $seoService = app(ApplicationSeoService::class);
        $seoSettings = $seoService->settings();

        return Inertia::render('Admin/Branding', [
            'appLogoUrl' => $logoUrl,
            'heroMainUrl' => $heroMainUrl,
            'heroDevicesUrl' => $heroDevicesUrl,
            'appSeoSettings' => [
                'site_name' => $seoSettings['site_name'] ?? '',
                'title' => $seoSettings['title'] ?? '',
                'description' => $seoSettings['description'] ?? '',
                'keywords' => $seoSettings['keywords'] ?? '',
                'indexing_enabled' => (bool) ($seoSettings['indexing_enabled'] ?? true),
                'google_site_verification' => $seoSettings['google_site_verification'] ?? '',
                'og_image' => $seoSettings['og_image'] ?? '',
                'twitter_handle' => $seoSettings['twitter_handle'] ?? '',
                'locale' => $seoSettings['locale'] ?? 'fr_FR',
                'public_base_url' => config('app.url'),
                'sitemap_url' => rtrim((string) config('app.url'), '/') . '/sitemap.xml',
            ],
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
            'seo_site_name' => 'nullable|string|max:80',
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:300',
            'seo_keywords' => 'nullable|string|max:255',
            'seo_indexing_enabled' => 'sometimes|boolean',
            'seo_google_site_verification' => 'nullable|string|max:255',
            'seo_og_image' => 'nullable|url|max:500',
            'seo_twitter_handle' => 'nullable|string|max:50',
            'seo_locale' => 'nullable|string|max:10',
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

        app(ApplicationSeoService::class)->saveSettings([
            'site_name' => $request->input('seo_site_name'),
            'title' => $request->input('seo_title'),
            'description' => $request->input('seo_description'),
            'keywords' => $request->input('seo_keywords'),
            'indexing_enabled' => $request->boolean('seo_indexing_enabled'),
            'google_site_verification' => $request->input('seo_google_site_verification'),
            'og_image' => $request->input('seo_og_image'),
            'twitter_handle' => $request->input('seo_twitter_handle'),
            'locale' => $request->input('seo_locale'),
        ]);

        return redirect()->route('admin.branding')
            ->with('success', 'Branding et référencement de l’application mis à jour avec succès.');
    }
}


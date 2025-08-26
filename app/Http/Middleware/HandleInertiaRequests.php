<?php

namespace App\Http\Middleware;

use App\Settings\AppearanceSettings;
use App\Settings\GeneralSettings;
use App\Settings\LocalizationSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user()?->load('branches'),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'settings' => [
                'general' => app(GeneralSettings::class)->toArray(),
                'appearance' => app(AppearanceSettings::class)->toArray(),
                'localization' => app(LocalizationSettings::class)->toArray(),
            ],
            'translations' => $this->getTranslations($request),
        ];
    }

    /**
     * Get the translations for the current locale.
     */
    private function getTranslations(Request $request): array
    {
        $locale = app()->getLocale();
        $filePath = lang_path("{$locale}.json");

        if (File::exists($filePath)) {
            return json_decode(File::get($filePath), true) ?? [];
        }

        return [];
    }
}

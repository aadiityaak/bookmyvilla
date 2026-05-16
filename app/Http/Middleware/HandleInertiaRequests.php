<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Laravel\Fortify\Features;

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
        $branding = Cache::remember('app_branding', 60, function () {
            $default = [
                'app_name' => config('app.name'),
                'tagline' => null,
                'logo_url' => null,
                'primary_color' => null,
                'secondary_color' => null,
            ];

            if (! Schema::hasTable('app_settings')) {
                return $default;
            }

            $row = AppSetting::query()->where('key', 'branding')->first();
            $value = is_array($row?->value) ? $row->value : [];

            $logoPath = $value['logo_path'] ?? null;
            $logoUrl = is_string($logoPath) && $logoPath !== ''
                ? '/storage/' . ltrim($logoPath, '/')
                : null;

            return [
                'app_name' => is_string($value['app_name'] ?? null) && $value['app_name'] !== ''
                    ? $value['app_name']
                    : $default['app_name'],
                'tagline' => is_string($value['tagline'] ?? null) && $value['tagline'] !== ''
                    ? $value['tagline']
                    : null,
                'logo_url' => $logoUrl,
                'primary_color' => is_string($value['primary_color'] ?? null) && $value['primary_color'] !== ''
                    ? $value['primary_color']
                    : null,
                'secondary_color' => is_string($value['secondary_color'] ?? null) && $value['secondary_color'] !== ''
                    ? $value['secondary_color']
                    : null,
            ];
        });

        return [
            ...parent::share($request),
            'name' => $branding['app_name'] ?? config('app.name'),
            'branding' => $branding,
            'canRegister' => Features::enabled(Features::registration()),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

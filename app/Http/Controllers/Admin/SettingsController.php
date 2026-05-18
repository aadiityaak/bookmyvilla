<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function brandingEdit(): Response
    {
        return Inertia::render('admin/settings/Branding', [
            'settings' => $this->getSetting('branding', [
                'app_name' => config('app.name'),
                'tagline' => null,
                'primary_color' => null,
                'secondary_color' => null,
                'logo_path' => null,
            ]),
        ]);
    }

    public function brandingUpdate(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('app_settings')) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Tabel app_settings belum tersedia. Jalankan migration terlebih dulu.',
            ]);

            return back();
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'primary_color' => ['nullable', 'string', 'max:30'],
            'secondary_color' => ['nullable', 'string', 'max:30'],
            'logo_remove' => ['nullable', 'boolean'],
            'logo_file' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $current = $this->getSetting('branding', [
            'app_name' => config('app.name'),
            'tagline' => null,
            'primary_color' => null,
            'secondary_color' => null,
            'logo_path' => null,
        ]);

        $logoPath = $current['logo_path'] ?? null;

        if (($validated['logo_remove'] ?? false) && is_string($logoPath) && $logoPath !== '') {
            if (Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = null;
        }

        if ($request->file('logo_file')) {
            $newPath = $request->file('logo_file')->store('branding', 'public');

            if (is_string($logoPath) && $logoPath !== '' && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $newPath;
        }

        AppSetting::updateOrCreate(
            ['key' => 'branding'],
            [
                'value' => [
                    'app_name' => $validated['app_name'],
                    'tagline' => $validated['tagline'] ?? null,
                    'primary_color' => $validated['primary_color'] ?? null,
                    'secondary_color' => $validated['secondary_color'] ?? null,
                    'logo_path' => $logoPath,
                ],
            ],
        );

        Cache::forget('app_branding');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Branding tersimpan.']);

        return back();
    }

    public function sosmedEdit(): Response
    {
        return Inertia::render('admin/settings/Sosmed', [
            'settings' => $this->getSetting('sosmed', [
                'website' => null,
                'email' => null,
                'phone' => null,
                'whatsapp' => null,
                'instagram' => null,
                'facebook' => null,
                'tiktok' => null,
                'youtube' => null,
                'twitter' => null,
            ]),
        ]);
    }

    public function sosmedUpdate(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('app_settings')) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Tabel app_settings belum tersedia. Jalankan migration terlebih dulu.',
            ]);

            return back();
        }

        $validated = $request->validate([
            'website' => ['nullable', 'string', 'max:2048'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:60'],
            'instagram' => ['nullable', 'string', 'max:2048'],
            'facebook' => ['nullable', 'string', 'max:2048'],
            'tiktok' => ['nullable', 'string', 'max:2048'],
            'youtube' => ['nullable', 'string', 'max:2048'],
            'twitter' => ['nullable', 'string', 'max:2048'],
        ]);

        AppSetting::updateOrCreate(
            ['key' => 'sosmed'],
            [
                'value' => [
                    'website' => $validated['website'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'whatsapp' => $validated['whatsapp'] ?? null,
                    'instagram' => $validated['instagram'] ?? null,
                    'facebook' => $validated['facebook'] ?? null,
                    'tiktok' => $validated['tiktok'] ?? null,
                    'youtube' => $validated['youtube'] ?? null,
                    'twitter' => $validated['twitter'] ?? null,
                ],
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sosmed tersimpan.']);

        return back();
    }

    public function paymentEdit(): Response
    {
        $settings = $this->getSetting('payment', [
            'banks' => [],
            'qris_image_path' => null,
        ]);

        $banks = is_array($settings['banks'] ?? null) ? $settings['banks'] : [];

        if (count($banks) === 0) {
            $legacyBank = [
                'bank_name' => $settings['bank_name'] ?? null,
                'account_name' => $settings['bank_account_name'] ?? null,
                'account_number' => $settings['bank_account_number'] ?? null,
            ];

            $hasLegacyValue = false;
            foreach ($legacyBank as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $hasLegacyValue = true;
                    break;
                }
            }

            if ($hasLegacyValue) {
                $banks = [$legacyBank];
            }
        }

        return Inertia::render('admin/settings/Payment', [
            'settings' => [
                'banks' => array_values($banks),
                'qris_image_path' => $settings['qris_image_path'] ?? null,
            ],
        ]);
    }

    public function paymentUpdate(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('app_settings')) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Tabel app_settings belum tersedia. Jalankan migration terlebih dulu.',
            ]);

            return back();
        }

        $validated = $request->validate([
            'banks' => ['nullable', 'array', 'max:10'],
            'banks.*.bank_name' => ['nullable', 'string', 'max:80'],
            'banks.*.account_name' => ['nullable', 'string', 'max:120'],
            'banks.*.account_number' => ['nullable', 'string', 'max:60'],
            'qris_remove' => ['nullable', 'boolean'],
            'qris_file' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $current = $this->getSetting('payment', [
            'banks' => [],
            'qris_image_path' => null,
        ]);

        $qrisPath = $current['qris_image_path'] ?? null;

        if (($validated['qris_remove'] ?? false) && is_string($qrisPath) && $qrisPath !== '') {
            if (Storage::disk('public')->exists($qrisPath)) {
                Storage::disk('public')->delete($qrisPath);
            }

            $qrisPath = null;
        }

        if ($request->file('qris_file')) {
            $newPath = $request->file('qris_file')->store('payment', 'public');

            if (is_string($qrisPath) && $qrisPath !== '' && Storage::disk('public')->exists($qrisPath)) {
                Storage::disk('public')->delete($qrisPath);
            }

            $qrisPath = $newPath;
        }

        $banks = [];
        if (is_array($validated['banks'] ?? null)) {
            foreach ($validated['banks'] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $bankName = is_string($row['bank_name'] ?? null) ? trim($row['bank_name']) : '';
                $accountName = is_string($row['account_name'] ?? null) ? trim($row['account_name']) : '';
                $accountNumber = is_string($row['account_number'] ?? null) ? trim($row['account_number']) : '';

                if ($bankName === '' && $accountName === '' && $accountNumber === '') {
                    continue;
                }

                $banks[] = [
                    'bank_name' => $bankName !== '' ? $bankName : null,
                    'account_name' => $accountName !== '' ? $accountName : null,
                    'account_number' => $accountNumber !== '' ? $accountNumber : null,
                ];
            }
        }

        AppSetting::updateOrCreate(
            ['key' => 'payment'],
            [
                'value' => [
                    'banks' => $banks,
                    'qris_image_path' => $qrisPath,
                ],
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment tersimpan.']);

        return back();
    }

    private function getSetting(string $key, array $default): array
    {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $row = AppSetting::query()->where('key', $key)->first();
        $value = is_array($row?->value) ? $row->value : [];

        return array_merge($default, $value);
    }
}

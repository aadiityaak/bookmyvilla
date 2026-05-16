<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Models\Property;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\Tenant\ProfileController as TenantProfileController;

Route::get('/', function (Request $request) {
    $villas = Property::query()
        ->select(['id', 'type', 'name', 'featured_image', 'address'])
        ->where('status', 'published')
        ->where('type', 'villa')
        ->orderByDesc('id')
        ->limit(10)
        ->get()
        ->map(fn(Property $p) => [
            'id' => $p->id,
            'type' => $p->type,
            'name' => $p->name,
            'featured_image' => $p->featured_image,
            'address' => $p->address,
        ]);

    $kosts = Property::query()
        ->select(['id', 'type', 'name', 'featured_image', 'address'])
        ->where('status', 'published')
        ->where('type', 'kost')
        ->orderByDesc('id')
        ->limit(10)
        ->get()
        ->map(fn(Property $p) => [
            'id' => $p->id,
            'type' => $p->type,
            'name' => $p->name,
            'featured_image' => $p->featured_image,
            'address' => $p->address,
        ]);

    return Inertia::render('guest/Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'villas' => $villas,
        'kosts' => $kosts,
    ]);
})->name('home');

Route::get('explore', [ExploreController::class, 'index'])->name('explore.index');
Route::get('explore/{property}', [ExploreController::class, 'show'])->name('explore.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $role = $request->user()?->role;

        if (in_array($role, ['tenant', 'host', 'investor'], true)) {
            return Inertia::render('guest/Dashboard');
        }

        return Inertia::render('admin/Dashboard');
    })->name('dashboard');

    Route::get('profile', [TenantProfileController::class, 'edit'])->name('tenant.profile.edit');
    Route::patch('profile', [TenantProfileController::class, 'update'])->name('tenant.profile.update');

    Route::get('wilayah/regencies', function (Request $request) {
        if (! Schema::hasTable('reg_regencies')) {
            return response()->json([]);
        }

        $provinceId = $request->string('province_id')->toString();
        if ($provinceId === '') {
            return response()->json([]);
        }

        return DB::table('reg_regencies')
            ->select(['id', 'name'])
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->get();
    })->name('wilayah.regencies');
});

Route::middleware(['auth', 'role:tenant'])->group(function () {
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('my-property', [BookingController::class, 'index'])->name('my-property');
});

Route::middleware(['auth', 'role:host,admin,investor'])->group(function () {
    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
});

Route::middleware(['auth', 'role:host,admin'])->group(function () {
    Route::get('properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::patch('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('settings', '/admin/settings/branding');
    Route::get('settings/branding', [\App\Http\Controllers\Admin\SettingsController::class, 'brandingEdit'])->name('settings.branding');
    Route::patch('settings/branding', [\App\Http\Controllers\Admin\SettingsController::class, 'brandingUpdate'])->name('settings.branding.update');
    Route::get('settings/sosmed', [\App\Http\Controllers\Admin\SettingsController::class, 'sosmedEdit'])->name('settings.sosmed');
    Route::patch('settings/sosmed', [\App\Http\Controllers\Admin\SettingsController::class, 'sosmedUpdate'])->name('settings.sosmed.update');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
});

require __DIR__.'/settings.php';

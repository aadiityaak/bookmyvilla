<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, AppNotification $notification): RedirectResponse
    {
        abort_unless($request->user() && $notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $redirect = $request->string('redirect')->toString();
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            return redirect($redirect);
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        AppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}

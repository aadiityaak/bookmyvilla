<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => $request->string('q')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $users = User::query()
            ->when($filters['q'], function ($query, string $q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery
                        ->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->when($filters['role'], fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] === 'active', fn ($query) => $query->whereNull('disabled_at'))
            ->when($filters['status'] === 'disabled', fn ($query) => $query->whereNotNull('disabled_at'))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => optional($user->email_verified_at)?->toISOString(),
                'disabled_at' => optional($user->disabled_at)?->toISOString(),
                'created_at' => optional($user->created_at)?->toISOString(),
            ]);

        return Inertia::render('admin/users/Index', [
            'filters' => $filters,
            'users' => $users,
            'roles' => ['tenant', 'host', 'investor', 'admin'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/Create', [
            'roles' => ['tenant', 'host', 'investor', 'admin'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', 'string', Rule::in(['tenant', 'host', 'investor', 'admin'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => optional($user->email_verified_at)?->toISOString(),
                'disabled_at' => optional($user->disabled_at)?->toISOString(),
                'created_at' => optional($user->created_at)?->toISOString(),
            ],
            'roles' => ['tenant', 'host', 'investor', 'admin'],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['tenant', 'host', 'investor', 'admin'])],
            'disabled' => ['required', 'boolean'],
        ]);

        if ($request->user()?->id === $user->id && $validated['disabled']) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('You cannot disable your own account.')]);

            return back();
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->disabled_at = $validated['disabled'] ? now() : null;
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.edit', $user);
    }
}

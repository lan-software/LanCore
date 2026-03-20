<?php

namespace App\Http\Controllers\Users;

use App\Actions\User\ChangeRoles;
use App\Actions\User\DeleteUser;
use App\Actions\User\UpdateUserAttributes;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserBulkRoleRequest;
use App\Http\Requests\Users\UserIndexRequest;
use App\Http\Requests\Users\UserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly DeleteUser $deleteUser,
        private readonly ChangeRoles $changeRoles,
        private readonly UpdateUserAttributes $updateUserAttributes,
    ) {}

    public function index(UserIndexRequest $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles');

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->validated('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        $sortColumn = $request->validated('sort') ?? 'name';
        $sortDirection = $request->validated('direction') ?? 'asc';
        $query->orderBy($sortColumn, $sortDirection);

        $users = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'sort', 'direction', 'role', 'per_page']),
        ]);
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('users/Show', [
            'user' => $user->load('roles'),
            'availableRoles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $attributes = array_filter([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        if ($password = $request->validated('password')) {
            $attributes['password'] = $password;
        }

        $this->updateUserAttributes->execute($user, $attributes);

        if ($request->has('role_names')) {
            $this->authorize('syncRoles', $user);

            $roleNames = array_map(
                fn (string $r) => RoleName::from($r),
                $request->validated('role_names') ?? [],
            );
            $this->changeRoles->sync($user, ...$roleNames);
        }

        return back();
    }

    public function bulkAssignRole(UserBulkRoleRequest $request): RedirectResponse
    {
        $this->authorize('updateAny', User::class);

        $users = User::whereIn('id', $request->validated('ids'))->get();
        $role = RoleName::from($request->validated('role'));

        $this->changeRoles->assignBulk($users, $role);

        return back();
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('deleteAny', User::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $users = User::whereIn('id', $validated['ids'])->get();

        $this->deleteUser->executeBulk($users, $request->user());

        return back();
    }
}

<?php

namespace App\Http\Controllers\Users;

use App\Actions\User\ChangeRoles;
use App\Actions\User\DeleteUser;
use App\Actions\User\UpdateUserAttributes;
use App\Domain\Auth\Steam\Enums\SteamLinkStatus;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserBulkRoleRequest;
use App\Http\Requests\Users\UserIndexRequest;
use App\Http\Requests\Users\UserPersonalDataUpdateRequest;
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

        if ($steamStatus = $request->validated('steam_status')) {
            $query->whereSteamStatus(SteamLinkStatus::from($steamStatus));
        }

        $sortColumn = $request->validated('sort') ?? 'name';
        $sortDirection = $request->validated('direction') ?? 'asc';
        $query->orderBy($sortColumn, $sortDirection);

        $users = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();
        $users->getCollection()->transform(function (User $user): array {
            $row = $user->toArray();
            $row['steam_status'] = SteamLinkStatus::for($user)->value;

            return $row;
        });

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'sort', 'direction', 'role', 'steam_status', 'per_page']),
        ]);
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load('roles');

        $deletionRequests = DeletionRequest::query()
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->limit(10)
            ->get();

        return Inertia::render('users/Show', [
            'user' => array_merge($user->toArray(), [
                'pending_deletion_at' => $user->pending_deletion_at?->toIso8601String(),
                'anonymized_at' => $user->anonymized_at?->toIso8601String(),
                'deleted_at' => $user->deleted_at?->toIso8601String(),
                'steam_status' => SteamLinkStatus::for($user)->value,
                'profile_updated_at' => $user->profile_updated_at?->toIso8601String(),
            ]),
            'availableRoles' => Role::dropdownOptions(),
            'deletionRequests' => $deletionRequests,
            'orders' => Inertia::defer(fn () => $user->orders()
                ->with(['event:id,name'])
                ->latest()
                ->limit(50)
                ->get()),
            'tickets' => Inertia::defer(fn () => $this->collectAdminTickets($user)),
            'comments' => Inertia::defer(fn () => $user->comments()
                ->with(['article:id,title,slug'])
                ->latest()
                ->limit(50)
                ->get()),
        ]);
    }

    /**
     * Collect tickets relevant to the user, tagged with the role
     * the user plays on each ticket. The same ticket can appear up
     * to three times (owner / manager / assigned) — that is intentional
     * for admin visibility.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectAdminTickets(User $user): array
    {
        $tag = function ($tickets, string $role): array {
            return $tickets->map(fn ($ticket) => array_merge($ticket->toArray(), [
                'admin_role' => $role,
            ]))->all();
        };

        $owned = $user->ownedTickets()
            ->with(['ticketType:id,name', 'event:id,name'])
            ->latest()
            ->limit(50)
            ->get();

        $managed = $user->managedTickets()
            ->with(['ticketType:id,name', 'event:id,name'])
            ->latest()
            ->limit(50)
            ->get();

        $assigned = $user->assignedTickets()
            ->with(['ticketType:id,name', 'event:id,name'])
            ->latest('tickets.created_at')
            ->limit(50)
            ->get();

        return [
            ...$tag($owned, 'owned'),
            ...$tag($managed, 'managed'),
            ...$tag($assigned, 'assigned'),
        ];
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

    public function updatePersonalData(UserPersonalDataUpdateRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $attributes = $request->safe()->only([
            'phone',
            'street',
            'city',
            'zip_code',
            'country',
            'short_bio',
            'profile_description',
            'profile_emoji',
            'profile_visibility',
            'is_ticket_discoverable',
            'is_seat_visible_publicly',
        ]);

        $user->fill($attributes);

        if ($user->isDirty()) {
            $user->profile_updated_at = now();
            $user->save();
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

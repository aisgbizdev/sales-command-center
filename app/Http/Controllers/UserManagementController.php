<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Unit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $users = User::query()
            ->with(['unit:id,name', 'team:id,name'])
            ->withCount(['prospects', 'prospectLogs', 'chatReviews'])
            ->when($request->string('role')->toString(), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->integer('unit_id'), fn ($query, int $unitId) => $query->where('unit_id', $unitId))
            ->when($request->integer('team_id'), fn ($query, int $teamId) => $query->where('team_id', $teamId))
            ->when($request->string('q')->toString(), function ($query, string $keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return response()->json([
            'items' => collect($users->items())->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'roleLabel' => $user->roleLabel(),
                'unitId' => $user->unit_id ? (string) $user->unit_id : '',
                'unitName' => $user->unit?->name ?? '-',
                'teamId' => $user->team_id ? (string) $user->team_id : '',
                'teamName' => $user->team?->name ?? '-',
                'createdAtLabel' => $user->created_at?->format('d M Y') ?? '-',
                'usageCount' => $user->prospects_count + $user->prospect_logs_count + $user->chat_reviews_count,
                'canDelete' => $user->id !== $request->user()->id
                    && $user->prospects_count === 0
                    && $user->prospect_logs_count === 0
                    && $user->chat_reviews_count === 0,
            ])->values(),
            'meta' => [
                'currentPage' => $users->currentPage(),
                'lastPage' => $users->lastPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
            ],
            'filters' => [
                'current' => [
                    'q' => $request->string('q')->toString(),
                    'role' => $request->string('role')->toString(),
                    'unit_id' => $request->string('unit_id')->toString(),
                    'team_id' => $request->string('team_id')->toString(),
                ],
                ...$this->formOptions(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $validated = $this->validateUser($request);

        $user = User::query()->create($validated);

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'id' => $user->id,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $validated = $this->validateUser($request, $user);

        if ($user->id === $request->user()->id && $validated['role'] !== User::ROLE_SUPER_ADMIN) {
            return response()->json([
                'message' => 'Akun yang sedang login harus tetap Super Admin.',
            ], 422);
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'User berhasil diperbarui.']);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Akun yang sedang login tidak bisa dihapus.'], 422);
        }

        $usageCount = $user->prospects()->count()
            + $user->prospectLogs()->count()
            + $user->chatReviews()->count();

        if ($usageCount > 0) {
            return response()->json([
                'message' => 'User ini masih punya data historis, jadi tidak bisa dihapus.',
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::exists('roles', 'code')],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
        ]);

        $validated['unit_id'] = $validated['unit_id'] ?? null;
        $validated['team_id'] = $validated['team_id'] ?? null;

        if ($validated['role'] === User::ROLE_SUPER_ADMIN) {
            $validated['unit_id'] = null;
            $validated['team_id'] = null;
        }

        if ($validated['role'] === User::ROLE_KEPALA) {
            validator($validated, [
                'unit_id' => ['required', 'integer', 'exists:units,id'],
            ])->validate();

            $validated['team_id'] = null;
        }

        if (in_array($validated['role'], [User::ROLE_MANAGER, User::ROLE_PENJUALAN], true)) {
            validator($validated, [
                'unit_id' => ['required', 'integer', 'exists:units,id'],
                'team_id' => [
                    'required',
                    'integer',
                    Rule::exists('teams', 'id')->where('unit_id', $validated['unit_id']),
                ],
            ])->validate();
        }

        return $validated;
    }

    private function formOptions(): array
    {
        return [
            'roles' => Role::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['code', 'label'])
                ->map(fn (Role $role) => [
                    'value' => $role->code,
                    'label' => $role->label,
                ])
                ->values(),
            'units' => Unit::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Unit $unit) => [
                    'value' => (string) $unit->id,
                    'label' => $unit->name,
                ])
                ->values(),
            'teams' => Team::query()
                ->with('unit:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'unit_id'])
                ->map(fn (Team $team) => [
                    'value' => (string) $team->id,
                    'label' => $team->name,
                    'unitId' => (string) $team->unit_id,
                    'unitName' => $team->unit?->name ?? '-',
                ])
                ->values(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserMasterDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        return response()->json($this->payload());
    }

    public function storeRole(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        Role::query()->create($this->validateRole($request));

        return response()->json(['message' => 'Role berhasil ditambahkan.'], 201);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $validated = $this->validateRole($request, $role);

        if ($role->is_system) {
            $validated['code'] = $role->code;
        }

        $role->update($validated);

        return response()->json(['message' => 'Role berhasil diperbarui.']);
    }

    public function destroyRole(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        if ($role->is_system) {
            return response()->json(['message' => 'Role bawaan sistem tidak bisa dihapus.'], 422);
        }

        if (User::query()->where('role', $role->code)->exists()) {
            return response()->json(['message' => 'Role masih dipakai user.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role berhasil dihapus.']);
    }

    public function storeUnit(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        Unit::query()->create($this->validateUnit($request));

        return response()->json(['message' => 'Unit berhasil ditambahkan.'], 201);
    }

    public function updateUnit(Request $request, Unit $unit): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $unit->update($this->validateUnit($request, $unit));

        return response()->json(['message' => 'Unit berhasil diperbarui.']);
    }

    public function destroyUnit(Request $request, Unit $unit): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        if ($unit->users()->exists() || $unit->teams()->exists() || $unit->prospects()->exists()) {
            return response()->json(['message' => 'Unit masih dipakai data lain.'], 422);
        }

        $unit->delete();

        return response()->json(['message' => 'Unit berhasil dihapus.']);
    }

    public function storeTeam(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        Team::query()->create($this->validateTeam($request));

        return response()->json(['message' => 'Team berhasil ditambahkan.'], 201);
    }

    public function updateTeam(Request $request, Team $team): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        $team->update($this->validateTeam($request, $team));

        return response()->json(['message' => 'Team berhasil diperbarui.']);
    }

    public function destroyTeam(Request $request, Team $team): JsonResponse
    {
        abort_unless($request->user()->can('manage-users'), 403);

        if ($team->users()->exists() || $team->prospects()->exists()) {
            return response()->json(['message' => 'Team masih dipakai data lain.'], 422);
        }

        $team->delete();

        return response()->json(['message' => 'Team berhasil dihapus.']);
    }

    private function payload(): array
    {
        $roleUsage = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return [
            'roles' => Role::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'label' => $role->label,
                    'description' => $role->description,
                    'sortOrder' => $role->sort_order,
                    'isSystem' => $role->is_system,
                    'usageCount' => (int) ($roleUsage[$role->code] ?? 0),
                    'canDelete' => (int) ($roleUsage[$role->code] ?? 0) === 0,
                ])->values(),
            'units' => Unit::query()
                ->withCount(['users', 'teams', 'prospects'])
                ->orderBy('name')
                ->get()
                ->map(fn (Unit $unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'code' => $unit->code,
                    'usageCount' => $unit->users_count + $unit->teams_count + $unit->prospects_count,
                    'canDelete' => $unit->users_count === 0 && $unit->teams_count === 0 && $unit->prospects_count === 0,
                ])->values(),
            'teams' => Team::query()
                ->with(['unit:id,name'])
                ->withCount(['users', 'prospects'])
                ->orderBy('name')
                ->get()
                ->map(fn (Team $team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'code' => $team->code,
                    'unitId' => (string) $team->unit_id,
                    'unitName' => $team->unit?->name ?? '-',
                    'usageCount' => $team->users_count + $team->prospects_count,
                    'canDelete' => $team->users_count === 0 && $team->prospects_count === 0,
                ])->values(),
        ];
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'code')->ignore($role?->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    private function validateUnit(Request $request, ?Unit $unit = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')->ignore($unit?->id)],
        ]);
    }

    private function validateTeam(Request $request, ?Team $team = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('teams', 'code')->ignore($team?->id)],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
        ]);
    }
}

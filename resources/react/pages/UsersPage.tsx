import * as React from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { KeyRound, Pencil, Plus, Save, Search, Trash2, UserRoundCog } from "lucide-react";
import { toast } from "sonner";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";

import type { Option, TeamOption, UserMasterDataResponse, UsersResponse } from "@/types";
import { fetchJson, sendJson } from "@/lib/api";
import { formatNumber } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { DataTable, ErrorState, OverlayModal, buildQuery, LoadingState, NativeSelect, movePage } from "@/components/app/shared";

type UserItem = UsersResponse["items"][number];

type UserFormState = {
  name: string;
  email: string;
  role: string;
  unit_id: string;
  team_id: string;
  password: string;
};

const emptyForm: UserFormState = {
  name: "",
  email: "",
  role: "penjualan",
  unit_id: "",
  team_id: "",
  password: "",
};

export function UsersPage() {
  const [location, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const queryClient = useQueryClient();
  const [filtersOpen, setFiltersOpen] = React.useState(false);
  const [editingUser, setEditingUser] = React.useState<UserItem | null>(null);
  const [formOpen, setFormOpen] = React.useState(false);

  const users = useQuery({
    queryKey: ["users", queryString],
    queryFn: () => fetchJson<UsersResponse>(`/react-api/users${queryString}`),
  });
  const masterData = useQuery({
    queryKey: ["user-master-data"],
    queryFn: () => fetchJson<UserMasterDataResponse>("/react-api/user-master-data"),
  });

  const deleteUser = useMutation({
    mutationFn: (id: number) => sendJson<{ message: string }>(`/react-api/users/${id}`, {}, "DELETE"),
    onSuccess: async (response) => {
      toast.success(response.message);
      await queryClient.invalidateQueries({ queryKey: ["users"] });
    },
    onError: (error) => toast.error(readErrorMessage(error)),
  });

  if (users.isLoading) return <LoadingState label="Memuat daftar user..." />;
  if (users.isError || !users.data) return <ErrorState />;

  const { items, meta, filters } = users.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <CardTitle className="text-3xl">Manajemen User</CardTitle>
            <CardDescription>Tambah akun internal, atur role, unit, team, dan reset password user.</CardDescription>
          </div>
          <Button
            type="button"
            onClick={() => {
              setEditingUser(null);
              setFormOpen(true);
            }}
          >
            <Plus className="h-4 w-4" />
            Tambah User
          </Button>
        </CardHeader>
        <CardContent>
          <Button type="button" variant="secondary" className="w-full sm:w-auto" onClick={() => setFiltersOpen(true)}>
            Buka Filter
          </Button>
        </CardContent>
      </Card>

      <OverlayModal open={filtersOpen} title="Filter User" onClose={() => setFiltersOpen(false)}>
        <UserFilters
          current={filters.current}
          roles={filters.roles}
          units={filters.units}
          teams={filters.teams}
          onApply={(params) => {
            setLocation(`/users${params ? `?${params}` : ""}`);
            setFiltersOpen(false);
          }}
        />
      </OverlayModal>

      <OverlayModal
        open={formOpen}
        title={editingUser ? `Edit ${editingUser.name}` : "Tambah User"}
        subtitle="User"
        maxWidthClass="max-w-[760px]"
        onClose={() => setFormOpen(false)}
      >
        <UserForm
          user={editingUser}
          roles={filters.roles}
          units={filters.units}
          teams={filters.teams}
          onDone={async (message) => {
            toast.success(message);
            setFormOpen(false);
            await queryClient.invalidateQueries({ queryKey: ["users"] });
          }}
        />
      </OverlayModal>

      {masterData.isLoading ? (
        <LoadingState label="Memuat master data user..." />
      ) : masterData.isError || !masterData.data ? (
        <ErrorState />
      ) : (
        <MasterDataSection data={masterData.data} />
      )}

      <Card>
        <CardHeader>
          <CardTitle>Daftar User</CardTitle>
          <CardDescription>
            Menampilkan {formatNumber(items.length)} dari {formatNumber(meta.total)} user.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            headers={["Nama", "Role", "Unit / Team", "Dibuat", "Data", "Aksi"]}
            rows={items.map((item) => [
              <div key={`${item.id}-identity`}>
                <p className="font-medium text-[#fff2a2]">{item.name}</p>
                <p className="text-xs text-[#d9c995]/70">{item.email}</p>
              </div>,
              <Badge key={`${item.id}-role`} variant={item.role === "super_admin" ? "success" : "info"}>
                {item.roleLabel}
              </Badge>,
              <div key={`${item.id}-scope`}>
                <p>{item.unitName}</p>
                <p className="text-xs text-[#d9c995]/70">{item.teamName}</p>
              </div>,
              item.createdAtLabel,
              item.usageCount > 0 ? (
                <Badge key={`${item.id}-usage`} variant="warn">
                  {formatNumber(item.usageCount)} record
                </Badge>
              ) : (
                <Badge key={`${item.id}-usage`} variant="success">Kosong</Badge>
              ),
              <div key={`${item.id}-actions`} className="flex flex-wrap gap-2">
                <Button
                  type="button"
                  size="sm"
                  variant="secondary"
                  onClick={() => {
                    setEditingUser(item);
                    setFormOpen(true);
                  }}
                >
                  <Pencil className="h-4 w-4" />
                  Edit
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="danger"
                  disabled={!item.canDelete || deleteUser.isPending}
                  onClick={() => {
                    if (window.confirm(`Hapus user ${item.name}?`)) deleteUser.mutate(item.id);
                  }}
                >
                  <Trash2 className="h-4 w-4" />
                  Hapus
                </Button>
              </div>,
            ])}
            emptyMessage="Belum ada user yang cocok dengan filter ini."
          />

          <div className="mt-4 flex items-center justify-between rounded-[20px] border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#d9c995]">
            <span>
              Halaman {meta.currentPage} dari {meta.lastPage}
            </span>
            <div className="flex gap-2">
              <Button
                variant="secondary"
                size="sm"
                disabled={meta.currentPage <= 1}
                onClick={() => movePage(location, setLocation, meta.currentPage - 1)}
              >
                Prev
              </Button>
              <Button
                variant="secondary"
                size="sm"
                disabled={meta.currentPage >= meta.lastPage}
                onClick={() => movePage(location, setLocation, meta.currentPage + 1)}
              >
                Next
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

function UserFilters({
  current,
  roles,
  units,
  teams,
  onApply,
}: {
  current: Record<string, string>;
  roles: Option[];
  units: Option[];
  teams: TeamOption[];
  onApply: (params: string) => void;
}) {
  const [form, setForm] = React.useState({
    q: current.q ?? "",
    role: current.role ?? "",
    unit_id: current.unit_id ?? "",
    team_id: current.team_id ?? "",
  });
  const filteredTeams = teams.filter((team) => !form.unit_id || team.unitId === form.unit_id);

  return (
    <form
      className="grid gap-3 sm:grid-cols-2"
      onSubmit={(event) => {
        event.preventDefault();
        onApply(buildQuery(form));
      }}
    >
      <Input
        value={form.q}
        onChange={(event) => setForm((prev) => ({ ...prev, q: event.target.value }))}
        placeholder="Cari nama atau email"
        className="sm:col-span-2"
      />
      <NativeSelect
        value={form.role}
        onChange={(value) => setForm((prev) => ({ ...prev, role: value }))}
        placeholder="Semua role"
        options={roles}
      />
      <NativeSelect
        value={form.unit_id}
        onChange={(value) => setForm((prev) => ({ ...prev, unit_id: value, team_id: "" }))}
        placeholder="Semua unit"
        options={units}
      />
      <NativeSelect
        value={form.team_id}
        onChange={(value) => setForm((prev) => ({ ...prev, team_id: value }))}
        placeholder="Semua team"
        options={filteredTeams}
      />
      <Button type="submit" variant="secondary">
        <Search className="h-4 w-4" />
        Filter
      </Button>
    </form>
  );
}

function UserForm({
  user,
  roles,
  units,
  teams,
  onDone,
}: {
  user: UserItem | null;
  roles: Option[];
  units: Option[];
  teams: TeamOption[];
  onDone: (message: string) => void | Promise<void>;
}) {
  const [form, setForm] = React.useState<UserFormState>(() => userToForm(user));
  const filteredTeams = teams.filter((team) => !form.unit_id || team.unitId === form.unit_id);
  const isSuperAdmin = form.role === "super_admin";
  const needsUnit = form.role !== "super_admin";
  const needsTeam = form.role === "manager" || form.role === "penjualan";

  React.useEffect(() => {
    setForm(userToForm(user));
  }, [user]);

  const saveUser = useMutation({
    mutationFn: () => {
      const payload = {
        ...form,
        unit_id: isSuperAdmin ? "" : form.unit_id,
        team_id: needsTeam ? form.team_id : "",
      };

      return sendJson<{ message: string }>(
        user ? `/react-api/users/${user.id}` : "/react-api/users",
        payload,
        user ? "PATCH" : "POST"
      );
    },
    onSuccess: (response) => onDone(response.message),
    onError: (error) => toast.error(readErrorMessage(error)),
  });

  return (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        saveUser.mutate();
      }}
    >
      <div className="grid gap-3 sm:grid-cols-2">
        <label className="grid gap-2 text-sm text-[#d9c995]">
          Nama
          <Input
            value={form.name}
            onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
            placeholder="Nama lengkap"
            required
          />
        </label>
        <label className="grid gap-2 text-sm text-[#d9c995]">
          Email
          <Input
            type="email"
            value={form.email}
            onChange={(event) => setForm((prev) => ({ ...prev, email: event.target.value }))}
            placeholder="email@sgbcc.test"
            required
          />
        </label>
        <div className="grid gap-2 text-sm text-[#d9c995]">
          Role
          <NativeSelect
            value={form.role}
            onChange={(value) =>
              setForm((prev) => ({
                ...prev,
                role: value,
                unit_id: value === "super_admin" ? "" : prev.unit_id,
                team_id: value === "manager" || value === "penjualan" ? prev.team_id : "",
              }))
            }
            placeholder="Pilih role"
            options={roles}
          />
        </div>
        <label className="grid gap-2 text-sm text-[#d9c995]">
          {user ? "Password Baru" : "Password"}
          <Input
            type="password"
            value={form.password}
            onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
            placeholder={user ? "Kosongkan jika tidak reset" : "Minimal 8 karakter"}
            required={!user}
          />
        </label>
        <div className="grid gap-2 text-sm text-[#d9c995]">
          Unit
          <NativeSelect
            value={form.unit_id}
            onChange={(value) => setForm((prev) => ({ ...prev, unit_id: value, team_id: "" }))}
            placeholder={isSuperAdmin ? "Tidak perlu unit" : "Pilih unit"}
            options={units}
            className={isSuperAdmin ? "opacity-60" : ""}
          />
          {needsUnit && !form.unit_id ? <p className="text-xs text-amber-200">Role ini wajib punya unit.</p> : null}
        </div>
        <div className="grid gap-2 text-sm text-[#d9c995]">
          Team
          <NativeSelect
            value={form.team_id}
            onChange={(value) => setForm((prev) => ({ ...prev, team_id: value }))}
            placeholder={needsTeam ? "Pilih team" : "Tidak perlu team"}
            options={filteredTeams}
            className={!needsTeam ? "opacity-60" : ""}
          />
          {needsTeam && !form.team_id ? <p className="text-xs text-amber-200">Manager dan Sales wajib punya team.</p> : null}
        </div>
      </div>

      <div className="rounded-[20px] border border-white/10 bg-white/5 p-4 text-sm text-[#d9c995]">
        <div className="flex items-start gap-3">
          {user ? <KeyRound className="mt-0.5 h-4 w-4 text-[#d9c995]" /> : <UserRoundCog className="mt-0.5 h-4 w-4 text-[#d9c995]" />}
          <p>
            {user
              ? "Isi password hanya kalau mau reset. Jika kosong, password lama tetap dipakai."
              : "User baru bisa langsung login memakai email dan password yang dibuat di sini."}
          </p>
        </div>
      </div>

      <div className="flex justify-end">
        <Button type="submit" disabled={saveUser.isPending}>
          <Save className="h-4 w-4" />
          Simpan User
        </Button>
      </div>
    </form>
  );
}

function MasterDataSection({ data }: { data: UserMasterDataResponse }) {
  const queryClient = useQueryClient();
  const [tab, setTab] = React.useState<"roles" | "units" | "teams">("roles");
  const [editing, setEditing] = React.useState<any | null>(null);
  const [formOpen, setFormOpen] = React.useState(false);

  const deleteItem = useMutation({
    mutationFn: ({ url }: { url: string }) => sendJson<{ message: string }>(url, {}, "DELETE"),
    onSuccess: async (response) => {
      toast.success(response.message);
      await queryClient.invalidateQueries({ queryKey: ["user-master-data"] });
      await queryClient.invalidateQueries({ queryKey: ["users"] });
    },
    onError: (error) => toast.error(readErrorMessage(error)),
  });

  const rows =
    tab === "roles"
      ? data.roles.map((role) => [
          <div key={`${role.id}-role`}>
            <p className="font-medium text-[#fff2a2]">{role.label}</p>
            <p className="text-xs text-[#d9c995]/70">{role.code}</p>
          </div>,
          role.description ?? "-",
          <Badge key={`${role.id}-role-system`} variant={role.isSystem ? "success" : "info"}>
            {role.isSystem ? "System" : "Custom"}
          </Badge>,
          <UsageBadge key={`${role.id}-role-usage`} count={role.usageCount} />,
          <MasterActions
            key={`${role.id}-role-actions`}
            canDelete={role.canDelete}
            onEdit={() => {
              setEditing(role);
              setFormOpen(true);
            }}
            onDelete={() => {
              if (window.confirm(`Hapus role ${role.label}?`)) {
                deleteItem.mutate({ url: `/react-api/user-master-data/roles/${role.id}` });
              }
            }}
          />,
        ])
      : tab === "units"
        ? data.units.map((unit) => [
            <div key={`${unit.id}-unit`}>
              <p className="font-medium text-[#fff2a2]">{unit.name}</p>
              <p className="text-xs text-[#d9c995]/70">{unit.code}</p>
            </div>,
            <UsageBadge key={`${unit.id}-unit-usage`} count={unit.usageCount} />,
            <MasterActions
              key={`${unit.id}-unit-actions`}
              canDelete={unit.canDelete}
              onEdit={() => {
                setEditing(unit);
                setFormOpen(true);
              }}
              onDelete={() => {
                if (window.confirm(`Hapus unit ${unit.name}?`)) {
                  deleteItem.mutate({ url: `/react-api/user-master-data/units/${unit.id}` });
                }
              }}
            />,
          ])
        : data.teams.map((team) => [
            <div key={`${team.id}-team`}>
              <p className="font-medium text-[#fff2a2]">{team.name}</p>
              <p className="text-xs text-[#d9c995]/70">{team.code}</p>
            </div>,
            team.unitName,
            <UsageBadge key={`${team.id}-team-usage`} count={team.usageCount} />,
            <MasterActions
              key={`${team.id}-team-actions`}
              canDelete={team.canDelete}
              onEdit={() => {
                setEditing(team);
                setFormOpen(true);
              }}
              onDelete={() => {
                if (window.confirm(`Hapus team ${team.name}?`)) {
                  deleteItem.mutate({ url: `/react-api/user-master-data/teams/${team.id}` });
                }
              }}
            />,
          ]);

  return (
    <Card>
      <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <CardTitle>Master Data User</CardTitle>
          <CardDescription>Kelola role, unit, dan team dari halaman yang sama.</CardDescription>
        </div>
        <Button
          type="button"
          variant="secondary"
          onClick={() => {
            setEditing(null);
            setFormOpen(true);
          }}
        >
          <Plus className="h-4 w-4" />
          Tambah {tab === "roles" ? "Role" : tab === "units" ? "Unit" : "Team"}
        </Button>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex flex-wrap gap-2">
          {[
            ["roles", "Role"],
            ["units", "Unit"],
            ["teams", "Team"],
          ].map(([key, label]) => (
            <Button
              key={key}
              type="button"
              variant={tab === key ? "default" : "secondary"}
              size="sm"
              onClick={() => {
                setTab(key as "roles" | "units" | "teams");
                setEditing(null);
              }}
            >
              {label}
            </Button>
          ))}
        </div>

        <DataTable
          headers={tab === "roles" ? ["Role", "Deskripsi", "Tipe", "Data", "Aksi"] : tab === "units" ? ["Unit", "Data", "Aksi"] : ["Team", "Unit", "Data", "Aksi"]}
          rows={rows}
          emptyMessage={`Belum ada ${tab === "roles" ? "role" : tab === "units" ? "unit" : "team"}.`}
        />
      </CardContent>

      <OverlayModal
        open={formOpen}
        title={`${editing ? "Edit" : "Tambah"} ${tab === "roles" ? "Role" : tab === "units" ? "Unit" : "Team"}`}
        subtitle="Master Data"
        maxWidthClass="max-w-[680px]"
        onClose={() => setFormOpen(false)}
      >
        <MasterForm
          type={tab}
          item={editing}
          units={data.units.map((unit) => ({ value: String(unit.id), label: unit.name }))}
          onDone={async (message) => {
            toast.success(message);
            setFormOpen(false);
            await queryClient.invalidateQueries({ queryKey: ["user-master-data"] });
            await queryClient.invalidateQueries({ queryKey: ["users"] });
          }}
        />
      </OverlayModal>
    </Card>
  );
}

function UsageBadge({ count }: { count: number }) {
  return count > 0 ? <Badge variant="warn">{formatNumber(count)} record</Badge> : <Badge variant="success">Kosong</Badge>;
}

function MasterActions({
  canDelete,
  onEdit,
  onDelete,
}: {
  canDelete: boolean;
  onEdit: () => void;
  onDelete: () => void;
}) {
  return (
    <div className="flex flex-wrap gap-2">
      <Button type="button" size="sm" variant="secondary" onClick={onEdit}>
        <Pencil className="h-4 w-4" />
        Edit
      </Button>
      <Button type="button" size="sm" variant="danger" disabled={!canDelete} onClick={onDelete}>
        <Trash2 className="h-4 w-4" />
        Hapus
      </Button>
    </div>
  );
}

function MasterForm({
  type,
  item,
  units,
  onDone,
}: {
  type: "roles" | "units" | "teams";
  item: any | null;
  units: Option[];
  onDone: (message: string) => void | Promise<void>;
}) {
  const [form, setForm] = React.useState(() => masterInitialForm(type, item));

  React.useEffect(() => {
    setForm(masterInitialForm(type, item));
  }, [type, item]);

  const save = useMutation({
    mutationFn: () => {
      const baseUrl = `/react-api/user-master-data/${type}`;
      const url = item ? `${baseUrl}/${item.id}` : baseUrl;
      return sendJson<{ message: string }>(url, form, item ? "PATCH" : "POST");
    },
    onSuccess: (response) => onDone(response.message),
    onError: (error) => toast.error(readErrorMessage(error)),
  });

  return (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        save.mutate();
      }}
    >
      {type === "roles" ? (
        <div className="grid gap-3 sm:grid-cols-2">
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Kode Role
            <Input
              value={form.code}
              onChange={(event) => setForm((prev) => ({ ...prev, code: event.target.value }))}
              placeholder="contoh: admin_cabang"
              required
            />
          </label>
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Label
            <Input
              value={form.label}
              onChange={(event) => setForm((prev) => ({ ...prev, label: event.target.value }))}
              placeholder="Admin Cabang"
              required
            />
          </label>
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Urutan
            <Input
              type="number"
              min="0"
              value={form.sort_order}
              onChange={(event) => setForm((prev) => ({ ...prev, sort_order: event.target.value }))}
            />
          </label>
          <label className="grid gap-2 text-sm text-[#d9c995] sm:col-span-2">
            Deskripsi
            <Input
              value={form.description}
              onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
              placeholder="Catatan fungsi role"
            />
          </label>
        </div>
      ) : type === "units" ? (
        <div className="grid gap-3 sm:grid-cols-2">
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Nama Unit
            <Input value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} required />
          </label>
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Kode Unit
            <Input value={form.code} onChange={(event) => setForm((prev) => ({ ...prev, code: event.target.value }))} required />
          </label>
        </div>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2">
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Nama Team
            <Input value={form.name} onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))} required />
          </label>
          <label className="grid gap-2 text-sm text-[#d9c995]">
            Kode Team
            <Input value={form.code} onChange={(event) => setForm((prev) => ({ ...prev, code: event.target.value }))} required />
          </label>
          <div className="grid gap-2 text-sm text-[#d9c995] sm:col-span-2">
            Unit
            <NativeSelect
              value={form.unit_id}
              onChange={(value) => setForm((prev) => ({ ...prev, unit_id: value }))}
              placeholder="Pilih unit"
              options={units}
            />
          </div>
        </div>
      )}

      <div className="flex justify-end">
        <Button type="submit" disabled={save.isPending}>
          <Save className="h-4 w-4" />
          Simpan
        </Button>
      </div>
    </form>
  );
}

function masterInitialForm(type: "roles" | "units" | "teams", item: any | null) {
  if (type === "roles") {
    return {
      code: item?.code ?? "",
      label: item?.label ?? "",
      description: item?.description ?? "",
      sort_order: String(item?.sortOrder ?? 100),
    };
  }

  if (type === "units") {
    return {
      name: item?.name ?? "",
      code: item?.code ?? "",
    };
  }

  return {
    name: item?.name ?? "",
    code: item?.code ?? "",
    unit_id: item?.unitId ?? "",
  };
}

function userToForm(user: UserItem | null): UserFormState {
  if (!user) return emptyForm;

  return {
    name: user.name,
    email: user.email,
    role: user.role,
    unit_id: user.unitId,
    team_id: user.teamId,
    password: "",
  };
}

function readErrorMessage(error: unknown) {
  const fallback = "Operasi user gagal.";

  if (!(error instanceof Error)) return fallback;

  try {
    const parsed = JSON.parse(error.message);
    if (parsed.message) return parsed.message;
  } catch {
    return error.message || fallback;
  }

  return fallback;
}

import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { Eye, Pencil, Plus, Search } from "lucide-react";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";

import type { Option, ProspectsResponse } from "@/types";
import { fetchJson } from "@/lib/api";
import { formatNumber } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { DataTable, ErrorState, OverlayModal, buildQuery, followUpLabel, followUpVariant, LoadingState, movePage, NativeSelect, priorityVariant, statusVariant } from "@/components/app/shared";

export function ProspectsPage() {
  const [location, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const [filtersOpen, setFiltersOpen] = React.useState(false);

  const prospects = useQuery({
    queryKey: ["prospects", queryString],
    queryFn: () => fetchJson<ProspectsResponse>(`/react-api/prospects${queryString}`),
  });

  if (prospects.isLoading) return <LoadingState label="Memuat daftar prospek..." />;
  if (prospects.isError || !prospects.data) return <ErrorState />;

  const { items, meta, filters, permissions } = prospects.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <CardTitle className="text-3xl">Manajemen Lead</CardTitle>
            <CardDescription>List kerja harian utama. Capture cepat, follow up cepat, enrich data saat sudah qualify.</CardDescription>
          </div>
          {permissions.canCreateProspect ? (
            <a href={permissions.createUrl}>
              <Button>
                <Plus className="h-4 w-4" />
                Tambah Lead
              </Button>
            </a>
          ) : null}
        </CardHeader>
        <CardContent>
          <Button type="button" variant="secondary" className="w-full sm:w-auto" onClick={() => setFiltersOpen(true)}>
            Buka Filter
          </Button>
        </CardContent>
      </Card>

      <OverlayModal open={filtersOpen} title="Filter Prospek" onClose={() => setFiltersOpen(false)}>
        <ProspectFilters
          current={filters.current}
          statuses={filters.statuses}
          accountCategories={filters.accountCategories}
          gptModes={filters.gptModes}
          userTemperatures={filters.userTemperatures}
          dominantEmotions={filters.dominantEmotions}
          bridgeStatuses={filters.bridgeStatuses}
          lostReasons={filters.lostReasons}
          salesUsers={filters.salesUsers}
          onApply={(params) => {
            setLocation(`/prospects${params ? `?${params}` : ""}`);
            setFiltersOpen(false);
          }}
        />
      </OverlayModal>

      <Card>
        <CardHeader>
          <CardTitle>Daftar Lead</CardTitle>
          <CardDescription>
            Menampilkan {formatNumber(items.length)} dari {formatNumber(meta.total)} lead.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            headers={["Kode", "Nama", "Kategori", "AI Qualification", "Owner", "Status", "Priority", "Aging", "Last Activity", "Follow Up", "Aksi"]}
            rows={items.map((item) => [
              item.prospectCode,
              <div key={`${item.id}-name`}>
                <p className="font-medium text-[#fff2a2]">{item.name}</p>
                <p className="text-xs text-[#d9c995]/70">{item.company}</p>
                <p className="text-xs text-[#d9c995]/70">{item.team} / {item.unit}</p>
              </div>,
              <div key={`${item.id}-category`} className="space-y-2">
                <Badge variant="info">{item.accountCategoryLabel}</Badge>
                <div className="text-xs text-[#d9c995]/70">{item.bridgeStatusLabel}</div>
              </div>,
              <div key={`${item.id}-qualification`} className="space-y-2 text-xs text-[#d9c995]">
                <div>GPT: {item.gptModeLabel}</div>
                <div>Suhu: {item.userTemperatureLabel}</div>
                <div>Emosi: {item.dominantEmotionLabel}</div>
                <div>Lost: {item.lostReasonLabel}</div>
                {item.mainObjection ? <Badge variant="warn">{item.mainObjection}</Badge> : null}
                {item.bridgeCandidate ? <Badge variant="warn">Bridge Candidate</Badge> : null}
              </div>,
              item.owner,
              <Badge key={`${item.id}-status`} variant={statusVariant(item.status)}>
                {item.statusLabel}
              </Badge>,
              <div key={`${item.id}-priority`} className="space-y-2">
                <Badge variant={priorityVariant(item.priority_level)}>{item.priority_level}</Badge>
                <div>
                  <Badge variant={followUpVariant(item.follow_up_state)}>
                    {followUpLabel(item.follow_up_state, item.overdue_days)}
                  </Badge>
                </div>
              </div>,
              <div key={`${item.id}-aging`} className="space-y-2">
                <span>{item.aging_days} hari</span>
                {item.is_stale ? <Badge variant="danger">Stale</Badge> : null}
              </div>,
              item.last_activity_diff,
              <div key={`${item.id}-followup`}>
                {item.nextFollowUpDateLabel}
                {item.isOverdue ? (
                  <div className="mt-2">
                    <Badge variant="danger">Terlambat</Badge>
                  </div>
                ) : null}
              </div>,
              <div key={`${item.id}-actions`} className="flex flex-wrap gap-2">
                <a href={item.showUrl}>
                  <Button size="sm" variant="secondary">
                    <Eye className="h-4 w-4" />
                    Detail
                  </Button>
                </a>
                {item.canEdit ? (
                  <a href={item.editUrl}>
                    <Button size="sm" variant="secondary">
                      <Pencil className="h-4 w-4" />
                      Edit
                    </Button>
                  </a>
                ) : null}
              </div>,
            ])}
            emptyMessage="Belum ada prospek yang cocok dengan filter ini."
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

function ProspectFilters({
  current,
  statuses,
  accountCategories,
  gptModes,
  userTemperatures,
  dominantEmotions,
  bridgeStatuses,
  lostReasons,
  salesUsers,
  onApply,
}: {
  current: Record<string, string>;
  statuses: Option[];
  accountCategories: Option[];
  gptModes: Option[];
  userTemperatures: Option[];
  dominantEmotions: Option[];
  bridgeStatuses: Option[];
  lostReasons: Option[];
  salesUsers: Option[];
  onApply: (params: string) => void;
}) {
  const [form, setForm] = React.useState({
    q: current.q ?? "",
    account_category: current.account_category ?? "",
    status: current.status ?? "",
    gpt_mode: current.gpt_mode ?? "",
    user_temperature: current.user_temperature ?? "",
    dominant_emotion: current.dominant_emotion ?? "",
    bridge_candidate: current.bridge_candidate ?? "",
    bridge_status: current.bridge_status ?? "",
    lost_reason: current.lost_reason ?? "",
    owner_id: current.owner_id ?? "",
    follow_up: current.follow_up ?? "",
  });

	  return (
	    <form
	      className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
	      onSubmit={(event) => {
	        event.preventDefault();
	        onApply(buildQuery(form));
	      }}
	    >
	      <div className="sm:col-span-2 lg:col-span-3">
	        <Input
	          value={form.q}
	          onChange={(event) => setForm((prev) => ({ ...prev, q: event.target.value }))}
	          placeholder="Cari nama, perusahaan, kode, atau nomor HP"
	        />
	      </div>
      <NativeSelect
        value={form.account_category}
        onChange={(value) => setForm((prev) => ({ ...prev, account_category: value }))}
        placeholder="Semua kategori"
        options={accountCategories}
      />
      <NativeSelect
        value={form.status}
        onChange={(value) => setForm((prev) => ({ ...prev, status: value }))}
        placeholder="Semua status"
        options={statuses}
      />
      <NativeSelect
        value={form.gpt_mode}
        onChange={(value) => setForm((prev) => ({ ...prev, gpt_mode: value }))}
        placeholder="Semua mode GPT"
        options={gptModes}
      />
      <NativeSelect
        value={form.user_temperature}
        onChange={(value) => setForm((prev) => ({ ...prev, user_temperature: value }))}
        placeholder="Semua suhu user"
        options={userTemperatures}
      />
      <NativeSelect
        value={form.dominant_emotion}
        onChange={(value) => setForm((prev) => ({ ...prev, dominant_emotion: value }))}
        placeholder="Semua emosi"
        options={dominantEmotions}
      />
      <NativeSelect
        value={form.owner_id}
        onChange={(value) => setForm((prev) => ({ ...prev, owner_id: value }))}
        placeholder="Semua owner"
        options={salesUsers}
      />
      <NativeSelect
        value={form.bridge_status}
        onChange={(value) => setForm((prev) => ({ ...prev, bridge_status: value }))}
        placeholder="Semua bridge status"
        options={bridgeStatuses}
      />
	      <NativeSelect
	        value={form.lost_reason}
	        onChange={(value) => setForm((prev) => ({ ...prev, lost_reason: value }))}
	        placeholder="Semua lost reason"
	        options={lostReasons}
	      />
	      <div className="grid gap-3 sm:col-span-2 lg:col-span-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
	        <NativeSelect
	          value={form.bridge_candidate}
	          onChange={(value) => setForm((prev) => ({ ...prev, bridge_candidate: value }))}
	          placeholder="Semua bridge candidate"
	          options={[
	            { value: "true", label: "Bridge Candidate" },
	            { value: "false", label: "Bukan Bridge Candidate" },
	          ]}
	        />
	        <NativeSelect
	          value={form.follow_up}
	          onChange={(value) => setForm((prev) => ({ ...prev, follow_up: value }))}
	          placeholder="Semua follow up"
	          options={[
	            { value: "overdue", label: "Terlambat" },
	            { value: "today", label: "Hari Ini" },
	            { value: "soon", label: "Due Soon" },
	            { value: "stale", label: "Stale Leads" },
	            { value: "week", label: "7 Hari" },
	          ]}
	        />
	        <Button type="submit" variant="secondary" className="w-full lg:w-auto">
	          <Search className="h-4 w-4" />
	          Filter
	        </Button>
	      </div>
	    </form>
	  );
	}

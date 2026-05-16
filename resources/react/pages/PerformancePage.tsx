import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";

import type { Option, PerformanceResponse } from "@/types";
import { fetchJson } from "@/lib/api";
import { formatPercent } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { DataTable, ErrorState, OverlayModal, buildQuery, LoadingState, NativeSelect, statusVariant } from "@/components/app/shared";

export function PerformancePage() {
  const [, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const [filtersOpen, setFiltersOpen] = React.useState(false);

  const performance = useQuery({
    queryKey: ["performance", queryString],
    queryFn: () => fetchJson<PerformanceResponse>(`/react-api/performance${queryString}`),
  });

  if (performance.isLoading) return <LoadingState label="Memuat performa..." />;
  if (performance.isError || !performance.data) return <ErrorState />;

  const { rows, statusBreakdown, overdueProspects, filters } = performance.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <CardTitle className="text-3xl">Kinerja Penjualan</CardTitle>
            <CardDescription>Bandingkan workload, output, dan titik bocor follow up dari data backend yang sekarang.</CardDescription>
          </div>
          <Button type="button" variant="secondary" className="w-full sm:w-auto" onClick={() => setFiltersOpen(true)}>
            Buka Filter
          </Button>
        </CardHeader>
      </Card>

      <OverlayModal open={filtersOpen} title="Filter Kinerja Penjualan" onClose={() => setFiltersOpen(false)} maxWidthClass="max-w-[760px]">
        <PerformanceFilters
          current={filters.current}
          accountCategories={filters.accountCategories}
          gptModes={filters.gptModes}
          userTemperatures={filters.userTemperatures}
          dominantEmotions={filters.dominantEmotions}
          bridgeStatuses={filters.bridgeStatuses}
          lostReasons={filters.lostReasons}
          salesUsers={filters.salesUsers}
          onApply={(params) => {
            setLocation(`/kinerja-penjualan${params ? `?${params}` : ""}`);
            setFiltersOpen(false);
          }}
        />
      </OverlayModal>

      <Card>
        <CardHeader>
          <CardTitle>Leaderboard Sales</CardTitle>
          <CardDescription>Ranking diurutkan dari closing lalu activity count.</CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            headers={["Sales", "Total", "Closing", "Mini", "Reguler", "Bridge", "Lost", "Aktivitas", "Overdue", "Estimasi", "Rasio"]}
            rows={rows.map((item) => [
              item.name,
              item.totalProspects,
              item.closingCount,
              item.miniClosingCount,
              item.regularClosingCount,
              item.bridgeConversionCount,
              item.lostCount,
              item.activityCount,
              item.overdueCount,
              item.totalValueLabel,
              `${formatPercent(item.ratio)}%`,
            ])}
            emptyMessage="Belum ada data performa untuk periode ini."
          />
        </CardContent>
      </Card>

      <div className="grid gap-4 xl:grid-cols-[1.2fr_minmax(380px,1fr)]">
        <Card>
          <CardHeader>
            <CardTitle>Distribusi Status</CardTitle>
            <CardDescription>Breakdown jumlah prospek per tahap.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3 md:grid-cols-2">
            {statusBreakdown.map((item) => (
              <div key={item.key} className="rounded-[20px] border border-white/10 bg-white/5 p-4">
                <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">{item.label}</p>
                <p className="mt-3 text-3xl font-semibold tracking-[-0.04em]">{item.total}</p>
              </div>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Prospek Terlambat</CardTitle>
            <CardDescription>Titik follow up yang perlu diprioritaskan.</CardDescription>
          </CardHeader>
          <CardContent>
            <DataTable
              headers={["Kode", "Prospek", "Owner", "Status", "Follow Up", "Aksi"]}
              rows={overdueProspects.map((item) => [
                item.prospectCode,
                item.name,
                item.owner,
                <Badge key={`${item.id}-status`} variant={statusVariant(item.status)}>
                  {item.statusLabel}
                </Badge>,
                <Badge key={`${item.id}-followup`} variant="danger">
                  {item.nextFollowUpDateLabel}
                </Badge>,
                <a key={`${item.id}-detail`} href={item.detailUrl} className="text-sm text-[#d9c995] underline-offset-4 hover:underline">
                  Detail
                </a>,
              ])}
              emptyMessage="Tidak ada prospek yang terlambat pada scope ini."
            />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Frekuensi Keberatan</CardTitle>
          <CardDescription>Snapshot keberatan yang paling sering muncul pada scope filter ini.</CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            headers={["Keberatan", "Jumlah"]}
            rows={performance.data.objectionFrequency.map((item) => [item.label, item.total])}
            emptyMessage="Belum ada keberatan yang tercatat."
          />
        </CardContent>
      </Card>
    </div>
  );
}

function PerformanceFilters({
  current,
  accountCategories,
  gptModes,
  userTemperatures,
  dominantEmotions,
  bridgeStatuses,
  lostReasons,
  salesUsers,
  onApply,
}: {
  current: Record<string, string> & { from: string; to: string };
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
    ...current,
    account_category: current.account_category ?? "",
    gpt_mode: current.gpt_mode ?? "",
    user_temperature: current.user_temperature ?? "",
    dominant_emotion: current.dominant_emotion ?? "",
    bridge_candidate: current.bridge_candidate ?? "",
    bridge_status: current.bridge_status ?? "",
    lost_reason: current.lost_reason ?? "",
    owner_id: current.owner_id ?? "",
  });

	  return (
	    <form
	      className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
	      onSubmit={(event) => {
	        event.preventDefault();
	        onApply(buildQuery(form));
	      }}
	    >
	      <Input type="date" value={form.from} onChange={(event) => setForm((prev) => ({ ...prev, from: event.target.value }))} />
	      <Input type="date" value={form.to} onChange={(event) => setForm((prev) => ({ ...prev, to: event.target.value }))} />
	      <NativeSelect
	        value={form.account_category}
	        onChange={(value) => setForm((prev) => ({ ...prev, account_category: value }))}
	        placeholder="Semua kategori"
	        options={accountCategories}
      />
      <NativeSelect value={form.gpt_mode} onChange={(value) => setForm((prev) => ({ ...prev, gpt_mode: value }))} placeholder="Semua mode GPT" options={gptModes} />
      <NativeSelect value={form.user_temperature} onChange={(value) => setForm((prev) => ({ ...prev, user_temperature: value }))} placeholder="Semua suhu user" options={userTemperatures} />
      <NativeSelect value={form.dominant_emotion} onChange={(value) => setForm((prev) => ({ ...prev, dominant_emotion: value }))} placeholder="Semua emosi" options={dominantEmotions} />
      <NativeSelect value={form.owner_id} onChange={(value) => setForm((prev) => ({ ...prev, owner_id: value }))} placeholder="Semua owner" options={salesUsers} />
	      <NativeSelect value={form.bridge_status} onChange={(value) => setForm((prev) => ({ ...prev, bridge_status: value }))} placeholder="Semua bridge status" options={bridgeStatuses} />
	      <NativeSelect value={form.lost_reason} onChange={(value) => setForm((prev) => ({ ...prev, lost_reason: value }))} placeholder="Semua lost reason" options={lostReasons} />
	      <div className="grid gap-3 sm:col-span-2 lg:col-span-3 lg:grid-cols-[minmax(0,1fr)_auto]">
	        <NativeSelect
	          value={form.bridge_candidate}
	          onChange={(value) => setForm((prev) => ({ ...prev, bridge_candidate: value }))}
	          placeholder="Semua bridge candidate"
	          options={[
	            { value: "true", label: "Bridge Candidate" },
	            { value: "false", label: "Bukan Bridge Candidate" },
	          ]}
	        />
	        <Button type="submit" variant="secondary" className="w-full lg:w-auto">
	          Terapkan
	        </Button>
	      </div>
	    </form>
	  );
	}

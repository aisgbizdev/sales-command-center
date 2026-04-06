import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { KanbanSquare, Plus } from "lucide-react";
import { useLocation } from "wouter";

import { boot, fetchJson } from "@/lib/api";
import { formatNumber, formatPercent } from "@/lib/utils";
import type { DashboardResponse } from "@/types";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import type { Option } from "@/types";
import { DataTable, ErrorState, buildQuery, getQueryString, LoadingState, MetricCard, MiniMetric, NativeSelect, statusVariant } from "@/components/app/shared";

export function DashboardPage() {
  const [location, setLocation] = useLocation();
  const queryString = getQueryString(location);
  const search = React.useMemo(() => new URLSearchParams(queryString), [queryString]);

  const dashboard = useQuery({
    queryKey: ["dashboard", queryString],
    queryFn: () => fetchJson<DashboardResponse>(`/react-api/dashboard${queryString}`),
  });

  if (dashboard.isLoading) return <LoadingState label="Memuat dashboard..." />;
  if (dashboard.isError || !dashboard.data) return <ErrorState />;

  const { kpis, statusSummary, upcomingFollowUp, lostReasonSummary, filters } = dashboard.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="grid gap-4 lg:grid-cols-[1.6fr_minmax(300px,1fr)]">
          <div>
            <CardTitle className="text-3xl">Dashboard Harian</CardTitle>
            <CardDescription>
              Versi React ini tetap baca data dari backend Laravel yang sama. Fokusnya buat ngetes alur visual dan ritme informasi.
            </CardDescription>
            <div className="mt-4 flex flex-wrap gap-3">
              <a href={boot.routes.prospectCreate}>
                <Button>
                  <Plus className="h-4 w-4" />
                  Input Prospek Baru
                </Button>
              </a>
              <a href="/pipeline">
                <Button variant="secondary">
                  <KanbanSquare className="h-4 w-4" />
                  Buka Pipeline
                </Button>
              </a>
            </div>
          </div>

          <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.2em] text-slate-500">Filter AI Sales</p>
            <div className="mt-4">
              <DashboardFilters current={filters.current} filters={filters} onApply={(params) => setLocation(`/dashboard${params ? `?${params}` : ""}`)} />
            </div>
            <div className="mt-4 grid grid-cols-2 gap-3">
              <MiniMetric label="Overdue" value={kpis.overdueCount} variant="danger" />
              <MiniMetric label="Due Today" value={kpis.dueTodayCount} variant="warn" />
            </div>
          </div>
        </CardHeader>
      </Card>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Total Prospek" value={kpis.totalProspects} note="Semua prospek yang masuk scope akses user." />
        <MetricCard label="Prospek Mini" value={kpis.miniProspects} note="Prospek dengan kategori akun mini." />
        <MetricCard label="Prospek Reguler" value={kpis.regularProspects} note="Prospek dengan kategori akun reguler." />
        <MetricCard label="Input Hari Ini" value={kpis.todayInputCount} note="Aktivitas yang tercatat pada hari ini." />
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Bridge Candidate" value={kpis.bridgeCandidatesCount} note="Prospek yang punya peluang transisi mini ke reguler." />
        <MetricCard label="Mini ke Reguler" value={kpis.bridgeMovedCount} note="Prospek dengan status bridge moved." />
        <MetricCard label="Konversi Mini" value={Math.round(kpis.miniConversionPercent)} note={`${formatPercent(kpis.miniConversionPercent)}% dari total mini.`} />
        <MetricCard label="Konversi Reguler" value={Math.round(kpis.regularConversionPercent)} note={`${formatPercent(kpis.regularConversionPercent)}% dari total reguler.`} />
      </div>

      <div className="grid gap-4 xl:grid-cols-[1.8fr_minmax(320px,1fr)]">
        <Card>
          <CardHeader>
            <CardTitle>Activity Overview</CardTitle>
            <CardDescription>Distribusi status prospek saat ini.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex h-64 items-end gap-3 rounded-[22px] border border-white/10 bg-white/5 p-4">
              {statusSummary.map((item) => {
                const max = Math.max(...statusSummary.map((entry) => entry.total), 1);
                const height = Math.max(14, Math.round((item.total / max) * 180));

                return (
                  <div key={item.key} className="flex flex-1 flex-col items-center gap-3">
                    <div
                      className="w-full max-w-7 rounded-full bg-[linear-gradient(180deg,#ffffff_0%,#8ca0bb_100%)] shadow-[0_10px_24px_rgba(255,255,255,0.08)]"
                      style={{ height }}
                    />
                    <span className="text-center text-[11px] text-slate-500">{item.label}</span>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Health Ratio</CardTitle>
            <CardDescription>Rasio penutupan terhadap total prospek.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="mx-auto mt-2 h-[95px] w-[190px] overflow-hidden rounded-t-full border-[14px] border-b-0 border-white/10">
              <div
                className="relative left-[-14px] top-[-14px] h-[95px] w-[190px] rounded-t-full border-[14px] border-b-0 border-white"
                style={{ clipPath: `inset(0 ${Math.max(0, 100 - kpis.healthPercent)}% 0 0)` }}
              />
            </div>
            <div className="-mt-2 text-center">
              <p className="text-5xl font-semibold tracking-[-0.05em]">{formatPercent(kpis.healthPercent)}%</p>
              <div className="mt-2 flex justify-center">
                <Badge variant={kpis.healthPercent >= 50 ? "success" : "warn"}>
                  {kpis.healthPercent >= 50 ? "On Track" : "Need Boost"}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Status Snapshot</CardTitle>
          <CardDescription>Ringkasan jumlah prospek per tahap.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          {statusSummary.map((item) => (
            <div key={item.key} className="rounded-[20px] border border-white/10 bg-white/5 p-4">
              <p className="text-xs uppercase tracking-[0.16em] text-slate-500">{item.label}</p>
              <p className="mt-3 text-3xl font-semibold tracking-[-0.04em]">{formatNumber(item.total)}</p>
            </div>
          ))}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Ringkasan Lost Reason</CardTitle>
          <CardDescription>Alasan kehilangan prospek yang paling sering muncul.</CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            headers={["Alasan", "Jumlah"]}
            rows={lostReasonSummary.map((item) => [item.label, formatNumber(item.total)])}
            emptyMessage="Belum ada lost reason yang tercatat."
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Follow Up Terdekat</CardTitle>
          <CardDescription>Daftar prospek yang perlu dicek duluan.</CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            headers={["Kode", "Prospek", "Owner", "Follow Up", "Status", "Aksi"]}
            rows={upcomingFollowUp.map((item) => [
              item.prospectCode,
              item.name,
              item.owner,
              item.nextFollowUpDateLabel,
              <Badge key={`${item.id}-status`} variant={statusVariant(item.status)}>
                {item.statusLabel}
              </Badge>,
              <a key={`${item.id}-detail`} href={item.detailUrl} className="text-sm text-slate-200 underline-offset-4 hover:underline">
                Lihat detail
              </a>,
            ])}
            emptyMessage="Belum ada follow up yang tercatat."
          />
        </CardContent>
      </Card>
    </div>
  );
}

function DashboardFilters({
  current,
  filters,
  onApply,
}: {
  current: Record<string, string>;
  filters: {
    accountCategories: Option[];
    gptModes: Option[];
    userTemperatures: Option[];
    dominantEmotions: Option[];
    bridgeStatuses: Option[];
    lostReasons: Option[];
  };
  onApply: (params: string) => void;
}) {
  const [form, setForm] = React.useState({
    account_category: current.account_category ?? "",
    gpt_mode: current.gpt_mode ?? "",
    user_temperature: current.user_temperature ?? "",
    dominant_emotion: current.dominant_emotion ?? "",
    bridge_candidate: current.bridge_candidate ?? "",
    bridge_status: current.bridge_status ?? "",
    lost_reason: current.lost_reason ?? "",
  });

  return (
    <form
      className="grid gap-3 md:grid-cols-2"
      onSubmit={(event) => {
        event.preventDefault();
        onApply(buildQuery(form));
      }}
    >
      <NativeSelect value={form.account_category} onChange={(value) => setForm((prev) => ({ ...prev, account_category: value }))} placeholder="Semua kategori" options={filters.accountCategories} />
      <NativeSelect value={form.gpt_mode} onChange={(value) => setForm((prev) => ({ ...prev, gpt_mode: value }))} placeholder="Semua mode GPT" options={filters.gptModes} />
      <NativeSelect value={form.user_temperature} onChange={(value) => setForm((prev) => ({ ...prev, user_temperature: value }))} placeholder="Semua suhu user" options={filters.userTemperatures} />
      <NativeSelect value={form.dominant_emotion} onChange={(value) => setForm((prev) => ({ ...prev, dominant_emotion: value }))} placeholder="Semua emosi" options={filters.dominantEmotions} />
      <NativeSelect
        value={form.bridge_candidate}
        onChange={(value) => setForm((prev) => ({ ...prev, bridge_candidate: value }))}
        placeholder="Semua bridge candidate"
        options={[
          { value: "true", label: "Bridge Candidate" },
          { value: "false", label: "Bukan Bridge Candidate" },
        ]}
      />
      <NativeSelect value={form.bridge_status} onChange={(value) => setForm((prev) => ({ ...prev, bridge_status: value }))} placeholder="Semua bridge status" options={filters.bridgeStatuses} />
      <div className="md:col-span-2 flex gap-3">
        <div className="flex-1">
          <NativeSelect value={form.lost_reason} onChange={(value) => setForm((prev) => ({ ...prev, lost_reason: value }))} placeholder="Semua lost reason" options={filters.lostReasons} />
        </div>
        <Button type="submit" variant="secondary">Terapkan</Button>
      </div>
    </form>
  );
}

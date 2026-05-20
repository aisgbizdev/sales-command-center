import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { KanbanSquare, Plus } from "lucide-react";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";

import { boot, fetchJson } from "@/lib/api";
import { formatNumber, formatPercent } from "@/lib/utils";
import type { ActionCenterResponse, DashboardResponse, SalesDisciplineMetric } from "@/types";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import type { Option } from "@/types";
import { DataTable, ErrorState, OverlayModal, buildQuery, followUpLabel, followUpVariant, healthVariant, LoadingState, MetricCard, MiniMetric, NativeSelect, priorityVariant, statusVariant } from "@/components/app/shared";

export function DashboardPage() {
  const [, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const search = React.useMemo(() => new URLSearchParams(queryString), [queryString]);
  const [filtersOpen, setFiltersOpen] = React.useState(false);

  const dashboard = useQuery({
    queryKey: ["dashboard", queryString],
    queryFn: () => fetchJson<DashboardResponse>(`/react-api/dashboard${queryString}`),
  });

  const actionCenter = useQuery({
    queryKey: ["action-center", queryString],
    queryFn: () => fetchJson<ActionCenterResponse>(`/react-api/action-center${queryString}`),
  });

  if (dashboard.isLoading) return <LoadingState label="Memuat dashboard..." />;
  if (dashboard.isError || !dashboard.data) return <ErrorState />;

  const { kpis, statusSummary, upcomingFollowUp, lostReasonSummary, disciplineSnapshot, filters } = dashboard.data;

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
            <p className="text-xs uppercase tracking-[0.2em] text-[#d9c995]/70">Filter AI Sales</p>
            <div className="mt-4">
              <Button type="button" variant="secondary" className="w-full" onClick={() => setFiltersOpen(true)}>
                Buka Filter
              </Button>
            </div>
            <div className="mt-4 grid grid-cols-2 gap-3">
              <MiniMetric label="Overdue" value={kpis.overdueCount} variant="danger" />
              <MiniMetric label="Due Today" value={kpis.dueTodayCount} variant="orange" />
              <MiniMetric label="Stale" value={kpis.staleCount} variant="danger" />
              <MiniMetric label="Stale + Overdue" value={kpis.staleOverdueCount} variant="danger" />
              <MiniMetric label="High Priority" value={kpis.highPriorityCount} variant="warn" />
              <MiniMetric label="> 7 Hari" value={kpis.agingOverSevenDaysCount} variant="warn" />
            </div>
          </div>
        </CardHeader>
      </Card>

      <OverlayModal open={filtersOpen} title="Filter AI Sales" onClose={() => setFiltersOpen(false)}>
        <DashboardFilters
          current={filters.current}
          filters={filters}
          onApply={(params) => {
            setLocation(`/dashboard${params ? `?${params}` : ""}`);
            setFiltersOpen(false);
          }}
        />
      </OverlayModal>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Total Prospek" value={kpis.totalProspects} note="Semua prospek yang masuk scope akses user." />
        <MetricCard label="Prospek Mini" value={kpis.miniProspects} note="Prospek dengan kategori akun mini." />
        <MetricCard label="Prospek Reguler" value={kpis.regularProspects} note="Prospek dengan kategori akun reguler." />
        <MetricCard label="Input Hari Ini" value={kpis.todayInputCount} note="Aktivitas yang tercatat pada hari ini." />
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="CRM Health" value={Math.round(kpis.crmHealthScore)} note={`${formatPercent(kpis.crmHealthScore)} score rata-rata discipline sales.`} />
        <MetricCard label="Total Stale Leads" value={kpis.staleCount} note="Lead aktif tanpa activity lebih dari 3 hari." />
        <MetricCard label="Overdue Ratio" value={Math.round(kpis.overdueRatio)} note={`${formatPercent(kpis.overdueRatio)}% active lead sudah telat follow up.`} />
        <MetricCard label="Active Sales Today" value={kpis.activeSalesTodayCount} note="Sales yang punya ProspectLog hari ini." />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <MetricCard label="Overdue Follow Up" value={kpis.overdueCount} note="Follow up aktif yang sudah melewati tanggal jadwal." />
        <MetricCard label="Due Today" value={kpis.dueTodayCount} note="Lead yang perlu disentuh hari ini." />
        <MetricCard label="Stale + Overdue" value={kpis.staleOverdueCount} note="Lead yang diam dan sudah telat follow up." />
        <MetricCard label="High Priority Leads" value={kpis.highPriorityCount} note="Lead critical atau high yang butuh action cepat." />
        <MetricCard label="Lead > 7 Hari di Stage" value={kpis.agingOverSevenDaysCount} note="Lead aktif yang terlalu lama di status saat ini." />
      </div>

      {actionCenter.data ? (
        <Card>
          <CardHeader>
            <CardTitle>Action Center</CardTitle>
            <CardDescription>Queue aksi prioritas untuk 2 jam kerja berikutnya.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
              <MiniMetric label="Overdue" value={actionCenter.data.summary.overdueCount} variant="danger" />
              <MiniMetric label="Warm Uncontacted" value={actionCenter.data.summary.warmUncontactedCount} variant="warn" />
              <MiniMetric label="Ghost Risk" value={actionCenter.data.summary.ghostRiskCount} variant="danger" />
              <MiniMetric label="Hot Opportunity" value={actionCenter.data.summary.hotOpportunityCount} variant="orange" />
            </div>

            <DataTable
              headers={["Queue", "Lead", "Owner", "Status", "Signal", "Priority", "Aksi"]}
              rows={[
                ...actionCenter.data.queues.overdue.map((item) => [
                  "Overdue",
                  `${item.prospectCode} - ${item.name}`,
                  item.owner,
                  <Badge key={`s-o-${item.id}`} variant={statusVariant(item.status)}>{item.statusLabel}</Badge>,
                  item.nextFollowUpDateLabel,
                  <Badge key={`p-o-${item.id}`} variant={priorityVariant(item.priorityLevel)}>{item.priorityLevel}</Badge>,
                  <a key={`a-o-${item.id}`} href={item.detailUrl} className="underline-offset-4 hover:underline">Open</a>,
                ]),
                ...actionCenter.data.queues.warmUncontacted.map((item) => [
                  "Warm Uncontacted",
                  `${item.prospectCode} - ${item.name}`,
                  item.owner,
                  <Badge key={`s-w-${item.id}`} variant={statusVariant(item.status)}>{item.statusLabel}</Badge>,
                  "Belum ada kontak",
                  <Badge key={`p-w-${item.id}`} variant={priorityVariant(item.priorityLevel)}>{item.priorityLevel}</Badge>,
                  <a key={`a-w-${item.id}`} href={item.detailUrl} className="underline-offset-4 hover:underline">Open</a>,
                ]),
                ...actionCenter.data.queues.ghostRisk.map((item) => [
                  "Ghost Risk",
                  `${item.prospectCode} - ${item.name}`,
                  item.owner,
                  <Badge key={`s-g-${item.id}`} variant={statusVariant(item.status)}>{item.statusLabel}</Badge>,
                  `${item.outbound_last_48h_count ?? 0} out / ${item.inbound_last_48h_count ?? 0} in (48h)`,
                  <Badge key={`p-g-${item.id}`} variant={priorityVariant(item.priorityLevel)}>{item.priorityLevel}</Badge>,
                  <a key={`a-g-${item.id}`} href={item.detailUrl} className="underline-offset-4 hover:underline">Open</a>,
                ]),
                ...actionCenter.data.queues.hotOpportunity.map((item) => [
                  "Hot Opportunity",
                  `${item.prospectCode} - ${item.name}`,
                  item.owner,
                  <Badge key={`s-h-${item.id}`} variant={statusVariant(item.status)}>{item.statusLabel}</Badge>,
                  item.lastActivityDiff,
                  <Badge key={`p-h-${item.id}`} variant={priorityVariant(item.priorityLevel)}>{item.priorityLevel}</Badge>,
                  <a key={`a-h-${item.id}`} href={item.detailUrl} className="underline-offset-4 hover:underline">Open</a>,
                ]),
              ]}
              emptyMessage="Belum ada item prioritas di Action Center."
            />
          </CardContent>
        </Card>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle>Discipline Snapshot</CardTitle>
          <CardDescription>Sinyal cepat buat coaching operasional hari ini.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3 lg:grid-cols-3">
          <DashboardInsightPanel title="Overdue Tertinggi" items={disciplineSnapshot.topOverdueSales} value={(item) => `${item.overdue_lead_count} overdue`} />
          <DashboardInsightPanel title="Paling Disiplin" items={disciplineSnapshot.mostDisciplinedSales} value={(item) => `${formatPercent(item.crm_activity_score)} score`} />
          <DashboardInsightPanel title="Tanpa Activity Hari Ini" items={disciplineSnapshot.salesWithoutActivityToday} value={() => "0 activity"} />
        </CardContent>
      </Card>

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
                    <span className="text-center text-[11px] text-[#d9c995]/70">{item.label}</span>
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
              <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">{item.label}</p>
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
            headers={["Kode", "Prospek", "Owner", "Follow Up", "Priority", "Status", "Aksi"]}
            rows={upcomingFollowUp.map((item) => [
              item.prospectCode,
              item.name,
              item.owner,
              <div key={`${item.id}-followup`} className="space-y-2">
                <Badge variant={followUpVariant(item.follow_up_state)}>
                  {followUpLabel(item.follow_up_state, item.overdue_days)}
                </Badge>
                <p className="text-xs text-[#d9c995]/70">{item.nextFollowUpDateLabel}</p>
              </div>,
              <Badge key={`${item.id}-priority`} variant={priorityVariant(item.priority_level)}>
                {item.priority_level}
              </Badge>,
              <Badge key={`${item.id}-status`} variant={statusVariant(item.status)}>
                {item.statusLabel}
              </Badge>,
              <a key={`${item.id}-detail`} href={item.detailUrl} className="text-sm text-[#d9c995] underline-offset-4 hover:underline">
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

function DashboardInsightPanel({
  title,
  items,
  value,
}: {
  title: string;
  items: SalesDisciplineMetric[];
  value: (item: SalesDisciplineMetric) => string;
}) {
  return (
    <div className="rounded-[18px] border border-white/10 bg-white/5 p-4">
      <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">{title}</p>
      <div className="mt-3 space-y-2">
        {items.length === 0 ? (
          <p className="text-sm text-[#d9c995]/70">Belum ada data.</p>
        ) : (
          items.map((item) => (
            <div key={item.sales_id} className="flex items-center justify-between gap-3 text-sm">
              <span className="truncate text-[#fff2a2]">{item.sales_name}</span>
              <Badge variant={healthVariant(item.health_state)}>{value(item)}</Badge>
            </div>
          ))
        )}
      </div>
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
    follow_up: current.follow_up ?? "",
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
      <NativeSelect
        value={form.follow_up}
        onChange={(value) => setForm((prev) => ({ ...prev, follow_up: value }))}
        placeholder="Semua follow up"
        options={[
          { value: "overdue", label: "Overdue Only" },
          { value: "today", label: "Due Today" },
          { value: "soon", label: "Due Soon" },
          { value: "stale", label: "Stale Leads" },
        ]}
      />
      <div className="md:col-span-2 flex flex-wrap gap-3">
        <div className="flex-1">
          <NativeSelect value={form.lost_reason} onChange={(value) => setForm((prev) => ({ ...prev, lost_reason: value }))} placeholder="Semua lost reason" options={filters.lostReasons} />
        </div>
        <Button type="submit" variant="secondary" className="w-full md:w-auto">Terapkan</Button>
      </div>
    </form>
  );
}

import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";

import type { ObjectionInsightsResponse, Option, PerformanceResponse, SalesDisciplineMetric } from "@/types";
import { fetchJson } from "@/lib/api";
import { formatPercent } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { DataTable, ErrorState, OverlayModal, buildQuery, followUpLabel, followUpVariant, healthVariant, LoadingState, NativeSelect, priorityVariant, statusVariant } from "@/components/app/shared";

export function PerformancePage() {
  const [, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const [filtersOpen, setFiltersOpen] = React.useState(false);

  const performance = useQuery({
    queryKey: ["performance", queryString],
    queryFn: () => fetchJson<PerformanceResponse>(`/react-api/performance${queryString}`),
  });
  const objectionInsights = useQuery({
    queryKey: ["objection-insights", queryString],
    queryFn: () => fetchJson<ObjectionInsightsResponse>(`/react-api/objection-insights${queryString}`),
  });

  if (performance.isLoading) return <LoadingState label="Memuat performa..." />;
  if (performance.isError || !performance.data) return <ErrorState />;

  const { rows, statusBreakdown, overdueProspects, operationalDiscipline, managerInsights, filters } = performance.data;

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

      <Card>
        <CardHeader>
          <CardTitle>Operational Discipline</CardTitle>
          <CardDescription>Kualitas ritme follow up, update CRM, dan hygiene pipeline per sales.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 lg:grid-cols-3">
            <InsightPanel title="Sales dengan overdue tertinggi" items={managerInsights.topOverdueSales} value={(item) => `${item.overdue_lead_count} overdue`} />
            <InsightPanel title="Sales paling disiplin" items={managerInsights.mostDisciplinedSales} value={(item) => `${formatPercent(item.crm_activity_score)} score`} />
            <InsightPanel title="Sales tanpa activity hari ini" items={managerInsights.salesWithoutActivityToday} value={() => "0 activity"} />
          </div>

          <div className="grid gap-3 xl:grid-cols-2">
            {operationalDiscipline.map((item) => (
              <div key={item.sales_id} className="rounded-[20px] border border-white/10 bg-white/5 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold text-[#fff2a2]">{item.sales_name}</p>
                    <p className="text-xs text-[#d9c995]/70">{item.active_lead_count} active lead | {item.daily_activity_count} activity hari ini</p>
                  </div>
                  <Badge variant={healthVariant(item.health_state)}>{item.health_state}</Badge>
                </div>
                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                  <DisciplineBar label="Follow Up Compliance" value={item.follow_up_compliance_rate} healthyHigh />
                  <DisciplineBar label="CRM Activity Score" value={item.crm_activity_score} healthyHigh />
                  <DisciplineBar label="Overdue Ratio" value={item.overdue_ratio} />
                  <DisciplineBar label="Stale Ratio" value={item.stale_lead_ratio} />
                </div>
                <div className="mt-4 grid gap-2 text-xs text-[#d9c995]/75 sm:grid-cols-3">
                  <span>{item.follow_up_lead_count} lead punya follow up</span>
                  <span>{item.overdue_lead_count} overdue</span>
                  <span>{formatPercent(item.avg_update_delay_hours)} jam avg delay</span>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {objectionInsights.data ? (
        <Card>
          <CardHeader>
            <CardTitle>Objection Intelligence</CardTitle>
            <CardDescription>Pola keberatan, risiko closing, dan bahan coaching script sales.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-3 lg:grid-cols-3">
              <ObjectionInsightCard
                title="Objection paling sering minggu ini"
                label={objectionInsights.data.managerInsights.mostCommonThisWeek?.label ?? "-"}
                value={objectionInsights.data.managerInsights.mostCommonThisWeek ? `${objectionInsights.data.managerInsights.mostCommonThisWeek.total} kasus` : "Belum ada data"}
              />
              <ObjectionInsightCard
                title="Closing turun pada objection"
                label={objectionInsights.data.managerInsights.lowestConversion?.label ?? "-"}
                value={objectionInsights.data.managerInsights.lowestConversion ? `${formatPercent(objectionInsights.data.managerInsights.lowestConversion.conversionRate)}% conversion` : "Belum ada data"}
              />
              <ObjectionInsightCard
                title="Regular account paling sering"
                label={objectionInsights.data.managerInsights.regularAccountTopObjection?.label ?? "-"}
                value={objectionInsights.data.managerInsights.regularAccountTopObjection ? `${objectionInsights.data.managerInsights.regularAccountTopObjection.total} kasus` : "Belum ada data"}
              />
            </div>

            <div className="grid gap-4 xl:grid-cols-3">
              <ObjectionBars title="Top Objections" items={objectionInsights.data.topObjections.slice(0, 8)} valueKey="total" />
              <ObjectionBars title="Hardest Objections" items={objectionInsights.data.highRiskObjections.slice(0, 8)} valueKey="riskScore" suffix=" risk" />
              <ObjectionBars title="Highest Conversion" items={objectionInsights.data.objectionConversion.slice(0, 8)} valueKey="conversionRate" suffix="%" />
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
              {objectionInsights.data.objectionByCategory.map((category) => (
                <div key={category.category} className="rounded-[20px] border border-white/10 bg-white/5 p-4">
                  <p className="text-sm font-semibold text-[#fff2a2]">{category.label}</p>
                  <div className="mt-3 space-y-2">
                    {category.items.length === 0 ? <p className="text-sm text-[#d9c995]/70">Belum ada data.</p> : category.items.map((item) => (
                      <div key={item.objection} className="flex items-center justify-between gap-3 text-sm text-[#d9c995]">
                        <span>{item.label}</span>
                        <Badge variant="warn">{item.total}</Badge>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      ) : null}

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
              headers={["Kode", "Prospek", "Owner", "Status", "Priority", "Follow Up", "Aksi"]}
              rows={overdueProspects.map((item) => [
                item.prospectCode,
                item.name,
                item.owner,
                <Badge key={`${item.id}-status`} variant={statusVariant(item.status)}>
                  {item.statusLabel}
                </Badge>,
                <Badge key={`${item.id}-priority`} variant={priorityVariant(item.priority_level)}>
                  {item.priority_level}
                </Badge>,
                <div key={`${item.id}-followup`} className="space-y-2">
                  <Badge variant={followUpVariant(item.follow_up_state)}>
                    {followUpLabel(item.follow_up_state, item.overdue_days)}
                  </Badge>
                  <p className="text-xs text-[#d9c995]/70">{item.nextFollowUpDateLabel}</p>
                </div>,
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

function InsightPanel({
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

function DisciplineBar({ label, value, healthyHigh = false }: { label: string; value: number; healthyHigh?: boolean }) {
  const variant = healthyHigh
    ? value >= 80 ? "success" : value >= 60 ? "warn" : "danger"
    : value >= 30 ? "danger" : value >= 15 ? "warn" : "success";
  const barClass = variant === "success" ? "bg-emerald-300" : variant === "warn" ? "bg-[#ffe37b]" : "bg-red-300";

  return (
    <div>
      <div className="flex items-center justify-between gap-3 text-xs text-[#d9c995]">
        <span>{label}</span>
        <span>{formatPercent(value)}%</span>
      </div>
      <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
        <div className={`h-full rounded-full ${barClass}`} style={{ width: `${Math.max(0, Math.min(100, value))}%` }} />
      </div>
    </div>
  );
}

function ObjectionInsightCard({ title, label, value }: { title: string; label: string; value: string }) {
  return (
    <div className="rounded-[18px] border border-white/10 bg-white/5 p-4">
      <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">{title}</p>
      <p className="mt-3 text-lg font-semibold text-[#fff2a2]">{label}</p>
      <p className="mt-1 text-sm text-[#d9c995]">{value}</p>
    </div>
  );
}

function ObjectionBars({
  title,
  items,
  valueKey,
  suffix = "",
}: {
  title: string;
  items: { label: string; total: number; conversionRate?: number; riskScore?: number }[];
  valueKey: "total" | "conversionRate" | "riskScore";
  suffix?: string;
}) {
  const max = Math.max(...items.map((item) => Number(item[valueKey] ?? 0)), 1);

  return (
    <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
      <p className="text-sm font-semibold text-[#fff2a2]">{title}</p>
      <div className="mt-4 space-y-3">
        {items.length === 0 ? (
          <p className="text-sm text-[#d9c995]/70">Belum ada objection tercatat.</p>
        ) : (
          items.map((item) => {
            const value = Number(item[valueKey] ?? 0);

            return (
              <div key={item.label}>
                <div className="flex items-center justify-between gap-3 text-xs text-[#d9c995]">
                  <span>{item.label}</span>
                  <span>{value}{suffix}</span>
                </div>
                <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                  <div className="h-full rounded-full bg-[#ffe37b]" style={{ width: `${Math.max(6, Math.round((value / max) * 100))}%` }} />
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}

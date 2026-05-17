import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { AlertTriangle, ArrowUpRight } from "lucide-react";
import { useSearch } from "wouter/use-browser-location";

import type { ManagerInsightsResponse, SalesDisciplineMetric } from "@/types";
import { fetchJson } from "@/lib/api";
import { formatNumber, formatPercent } from "@/lib/utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { DataTable, ErrorState, healthVariant, LoadingState, priorityVariant, statusVariant } from "@/components/app/shared";

export function ManagerInsightsPage() {
  const queryString = useSearch() ?? "";
  const insights = useQuery({
    queryKey: ["manager-insights", queryString],
    queryFn: () => fetchJson<ManagerInsightsResponse>(`/react-api/manager-insights${queryString}`),
  });

  if (insights.isLoading) return <LoadingState label="Memuat manager command center..." />;
  if (insights.isError || !insights.data) return <ErrorState />;

  const data = insights.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-3xl">Manager Command Center</CardTitle>
          <CardDescription>Scan cepat kondisi tim, urgency harian, bottleneck pipeline, dan bahan coaching objektif.</CardDescription>
        </CardHeader>
      </Card>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <HealthCard label="Active Leads" value={data.teamHealth.totalActiveLeads} state="healthy" />
        <HealthCard label="Overdue Leads" value={data.teamHealth.overdueLeads} state={data.teamHealth.overdueLeads > 0 ? "critical" : "healthy"} />
        <HealthCard label="Stale Leads" value={data.teamHealth.staleLeads} state={data.teamHealth.staleLeads > 0 ? "warning" : "healthy"} />
        <HealthCard label="Due Today" value={data.teamHealth.dueToday} state="warning" />
        <HealthCard label="Active Sales Today" value={data.teamHealth.activeSalesToday} state="healthy" />
        <HealthCard label="Inactive Sales Today" value={data.teamHealth.inactiveSalesToday} state={data.teamHealth.inactiveSalesToday > 0 ? "critical" : "healthy"} />
        <HealthCard label="Avg Compliance" value={Math.round(data.teamHealth.avgFollowUpCompliance)} suffix="%" state={data.teamHealth.avgFollowUpCompliance >= 80 ? "healthy" : data.teamHealth.avgFollowUpCompliance >= 60 ? "warning" : "critical"} />
        <HealthCard label="Avg CRM Score" value={Math.round(data.teamHealth.avgCrmActivityScore)} state={data.teamHealth.avgCrmActivityScore >= 80 ? "healthy" : data.teamHealth.avgCrmActivityScore >= 60 ? "warning" : "critical"} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Team Alerts</CardTitle>
          <CardDescription>Alert deterministic untuk action cepat hari ini.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          {data.alerts.map((alert, index) => (
            <div key={`${alert.message}-${index}`} className="rounded-[18px] border border-white/10 bg-white/5 p-4">
              <div className="flex items-center gap-2">
                <AlertTriangle className="h-4 w-4 text-[#ffe37b]" />
                <Badge variant={healthVariant(alert.level)}>{alert.level}</Badge>
              </div>
              <p className="mt-3 text-sm text-[#d9c995]">{alert.message}</p>
            </div>
          ))}
        </CardContent>
      </Card>

      <div className="grid gap-4 xl:grid-cols-2">
        <RankingCard title="Top Disciplined Sales" items={data.salesRanking.topDisciplined} mode="top" />
        <RankingCard title="Sales Perlu Coaching" items={data.salesRanking.needsAttention} mode="risk" />
      </div>

      <div className="grid gap-4 xl:grid-cols-[1fr_1.2fr]">
        <Card>
          <CardHeader>
            <CardTitle>Pipeline Bottleneck</CardTitle>
            <CardDescription>Stage dengan penumpukan dan lead stuck terbesar.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {data.pipelineBottleneck.length === 0 ? (
              <p className="text-sm text-[#d9c995]/70">Belum ada active lead.</p>
            ) : (
              data.pipelineBottleneck.map((item) => (
                <div key={item.status}>
                  <div className="flex items-center justify-between gap-3 text-sm text-[#d9c995]">
                    <span>{item.label}</span>
                    <span>{formatPercent(item.percent)}% | {item.stuckCount} stuck</span>
                  </div>
                  <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                    <div className="h-full rounded-full bg-[#ffe37b]" style={{ width: `${Math.max(6, item.percent)}%` }} />
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>High Priority Leads</CardTitle>
            <CardDescription>Lead yang paling perlu action sekarang.</CardDescription>
          </CardHeader>
          <CardContent>
            <DataTable
              headers={["Prospek", "Owner", "Status", "Overdue", "Follow Up", "Aksi"]}
              rows={data.priorityLeads.map((item) => [
                <div key={`${item.id}-name`}>
                  <p className="font-medium text-[#fff2a2]">{item.name}</p>
                  <p className="text-xs text-[#d9c995]/70">{item.prospectCode}</p>
                </div>,
                item.owner,
                <div key={`${item.id}-status`} className="space-y-2">
                  <Badge variant={statusVariant(item.status)}>{item.statusLabel}</Badge>
                  <Badge variant={priorityVariant(item.priorityLevel)}>{item.priorityLevel}</Badge>
                </div>,
                `${item.overdueDays} hari`,
                item.nextFollowUpDateLabel,
                <a key={`${item.id}-detail`} href={item.detailUrl}>
                  <Button size="sm" variant="secondary">
                    Detail
                    <ArrowUpRight className="h-4 w-4" />
                  </Button>
                </a>,
              ])}
              emptyMessage="Tidak ada high priority lead di scope ini."
            />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Objection Trend</CardTitle>
          <CardDescription>Bottleneck trust dan bahan update script sales.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-3">
          <TrendBox title="Paling Sering" label={data.objectionTrends.mostCommon?.label ?? "-"} value={data.objectionTrends.mostCommon ? `${data.objectionTrends.mostCommon.total} kasus` : "Belum ada data"} />
          <TrendBox title="Conversion Terburuk" label={data.objectionTrends.worstConversion?.label ?? "-"} value={data.objectionTrends.worstConversion ? `${formatPercent(data.objectionTrends.worstConversion.conversionRate)}% conversion` : "Belum ada data"} />
          <div className="rounded-[18px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">Trending Naik</p>
            <div className="mt-3 space-y-2">
              {data.objectionTrends.trendingUp.length === 0 ? (
                <p className="text-sm text-[#d9c995]/70">Belum ada data.</p>
              ) : (
                data.objectionTrends.trendingUp.map((item) => (
                  <div key={item.objection} className="flex items-center justify-between gap-3 text-sm">
                    <span className="text-[#fff2a2]">{item.label}</span>
                    <Badge variant="warn">{item.total}</Badge>
                  </div>
                ))
              )}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

function HealthCard({ label, value, suffix = "", state }: { label: string; value: number; suffix?: string; state: string }) {
  return (
    <Card>
      <CardContent className="space-y-3">
        <div className="flex items-center justify-between gap-3">
          <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">{label}</p>
          <Badge variant={healthVariant(state)}>{state}</Badge>
        </div>
        <p className="text-4xl font-semibold tracking-[-0.05em]">{formatNumber(value)}{suffix}</p>
      </CardContent>
    </Card>
  );
}

function RankingCard({ title, items, mode }: { title: string; items: SalesDisciplineMetric[]; mode: "top" | "risk" }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{mode === "top" ? "Score tinggi, compliance rapi, activity sehat." : "Score rendah, overdue/stale perlu ditindak."}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        {items.length === 0 ? (
          <p className="text-sm text-[#d9c995]/70">Belum ada data sales.</p>
        ) : (
          items.map((item) => (
            <div key={item.sales_id} className="rounded-[18px] border border-white/10 bg-white/5 p-4">
              <div className="flex items-center justify-between gap-3">
                <p className="font-semibold text-[#fff2a2]">{item.sales_name}</p>
                <Badge variant={healthVariant(item.health_state)}>{item.crm_activity_score}</Badge>
              </div>
              <div className="mt-3 grid gap-2 text-xs text-[#d9c995]/75 sm:grid-cols-4">
                <span>{formatPercent(item.follow_up_compliance_rate)}% comply</span>
                <span>{formatPercent(item.overdue_ratio)}% overdue</span>
                <span>{formatPercent(item.stale_lead_ratio)}% stale</span>
                <span>{item.daily_activity_count} activity</span>
              </div>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );
}

function TrendBox({ title, label, value }: { title: string; label: string; value: string }) {
  return (
    <div className="rounded-[18px] border border-white/10 bg-white/5 p-4">
      <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">{title}</p>
      <p className="mt-3 text-lg font-semibold text-[#fff2a2]">{label}</p>
      <p className="mt-1 text-sm text-[#d9c995]">{value}</p>
    </div>
  );
}

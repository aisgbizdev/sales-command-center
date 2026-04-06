import * as React from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ArrowUpRight, LoaderCircle } from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { toast } from "sonner";
import { useLocation } from "wouter";

import type { Option, PipelineResponse } from "@/types";
import { fetchJson, sendJson } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { buildQuery, ErrorState, getQueryString, LoadingState, MiniMetric, NativeSelect, statusVariant } from "@/components/app/shared";

const quickUpdateSchema = z.object({
  status: z.string().min(1),
  next_follow_up_date: z.string().optional(),
  quick_note: z.string().max(200).optional(),
  user_temperature: z.string().optional(),
  dominant_emotion: z.string().optional(),
  main_objection: z.string().optional(),
  bridge_candidate: z.boolean().optional(),
});
type QuickUpdateValues = z.infer<typeof quickUpdateSchema>;

export function PipelinePage() {
  const [location, setLocation] = useLocation();
  const queryString = getQueryString(location);

  const pipeline = useQuery({
    queryKey: ["pipeline", queryString],
    queryFn: () => fetchJson<PipelineResponse>(`/react-api/pipeline${queryString}`),
  });

  if (pipeline.isLoading) return <LoadingState label="Memuat pipeline..." />;
  if (pipeline.isError || !pipeline.data) return <ErrorState />;

  const { columns, metrics, filters } = pipeline.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="grid gap-4 lg:grid-cols-[1.5fr_minmax(320px,1fr)]">
          <div>
            <CardTitle className="text-3xl">Pipeline Board</CardTitle>
            <CardDescription>Quick update tetap nembak endpoint backend yang sama, cuma shell UI-nya sekarang React.</CardDescription>
          </div>
          <div className="grid gap-3 md:grid-cols-3">
            <MiniMetric label="Overdue" value={metrics.overdueCount} variant="danger" />
            <MiniMetric label="Due Today" value={metrics.dueTodayCount} variant="warn" />
            <MiniMetric label="<= 3 Hari" value={metrics.dueSoonCount} />
          </div>
        </CardHeader>
        <CardContent>
          <PipelineFilters
            current={filters.current}
            accountCategories={filters.accountCategories}
            gptModes={filters.gptModes}
            userTemperatures={filters.userTemperatures}
            dominantEmotions={filters.dominantEmotions}
            bridgeStatuses={filters.bridgeStatuses}
            lostReasons={filters.lostReasons}
            salesUsers={filters.salesUsers}
            onApply={(params) => setLocation(`/pipeline${params ? `?${params}` : ""}`)}
          />
        </CardContent>
      </Card>

      <div className="grid gap-4 overflow-x-auto pb-2 xl:grid-cols-4 2xl:grid-cols-7">
        {columns.map((column) => (
          <Card key={column.status} className="min-w-[300px]">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-base">{column.label}</CardTitle>
                <Badge variant="info">{column.count}</Badge>
              </div>
            </CardHeader>
            <CardContent className="space-y-3">
              {column.items.length === 0 ? (
                <div className="rounded-[18px] border border-dashed border-white/10 bg-white/5 p-4 text-sm text-slate-500">
                  Tidak ada prospek.
                </div>
              ) : (
                column.items.map((item) => <PipelineCard key={item.id} item={item} />)
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}

function PipelineCard({
  item,
}: {
  item: PipelineResponse["columns"][number]["items"][number];
}) {
  const queryClient = useQueryClient();
  const form = useForm<QuickUpdateValues>({
    resolver: zodResolver(quickUpdateSchema),
    defaultValues: {
      status: item.status,
      next_follow_up_date: item.nextFollowUpDate ?? "",
      quick_note: "",
      user_temperature: item.userTemperature ?? "",
      dominant_emotion: item.dominantEmotion ?? "",
      main_objection: item.mainObjection ?? "",
      bridge_candidate: item.bridgeCandidate,
    },
  });

  const mutation = useMutation({
    mutationFn: (values: QuickUpdateValues) =>
      sendJson<{ message: string }>(item.quickUpdateUrl, values, "PATCH"),
    onSuccess: async (data) => {
      toast.success(data.message);
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["pipeline"] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard"] }),
        queryClient.invalidateQueries({ queryKey: ["prospects"] }),
        queryClient.invalidateQueries({ queryKey: ["performance"] }),
      ]);
      form.reset({ ...(form.getValues() as QuickUpdateValues), quick_note: "" });
    },
    onError: () => toast.error("Quick update gagal disimpan."),
  });

  return (
    <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="font-medium text-white">{item.name}</p>
          <p className="text-xs text-slate-500">{item.company}</p>
        </div>
        <Badge variant="info">{item.prospectCode}</Badge>
      </div>

      <div className="mt-4 space-y-2 text-sm text-slate-400">
        <p>Owner: {item.owner}</p>
        <p>Akun: {item.accountCategoryLabel}</p>
        <div className="flex flex-wrap gap-2">
          <Badge variant={statusVariant(item.status)}>{item.statusLabel}</Badge>
          {item.isOverdue ? <Badge variant="danger">Terlambat</Badge> : null}
          {item.bridgeCandidate ? <Badge variant="warn">Bridge Candidate</Badge> : null}
        </div>
        <p>Follow Up: {item.nextFollowUpDateLabel}</p>
        <p>GPT: {item.gptModeLabel}</p>
        <p>Suhu: {item.userTemperatureLabel}</p>
        <p>Emosi: {item.dominantEmotionLabel}</p>
        <p>Bridge: {item.bridgeStatusLabel}</p>
      </div>

      {item.canEdit ? (
        <form className="mt-4 space-y-3" onSubmit={form.handleSubmit((values) => mutation.mutate(values))}>
          <select
            className="flex h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 text-sm text-white outline-none"
            {...form.register("status")}
          >
            <option className="text-slate-950" value="baru">Baru</option>
            <option className="text-slate-950" value="dihubungi">Dihubungi</option>
            <option className="text-slate-950" value="dibalas">Dibalas</option>
            <option className="text-slate-950" value="sedang_berjalan">Sedang Berjalan</option>
            <option className="text-slate-950" value="tindak_lanjut">Tindak Lanjut</option>
            <option className="text-slate-950" value="penutupan">Penutupan</option>
            <option className="text-slate-950" value="hilang">Hilang</option>
          </select>
          <Input type="date" {...form.register("next_follow_up_date")} />
          <Input placeholder="Apa hasil singkat update ini?" {...form.register("quick_note")} />
          <select className="flex h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 text-sm text-white outline-none" {...form.register("user_temperature")}>
            <option className="text-slate-950" value="">User temperature</option>
            <option className="text-slate-950" value="cold">Cold</option>
            <option className="text-slate-950" value="warm">Warm</option>
            <option className="text-slate-950" value="hot">Hot</option>
          </select>
          <select className="flex h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 text-sm text-white outline-none" {...form.register("dominant_emotion")}>
            <option className="text-slate-950" value="">Emosi dominan</option>
            <option className="text-slate-950" value="takut">Takut</option>
            <option className="text-slate-950" value="ragu">Ragu</option>
            <option className="text-slate-950" value="kritis">Kritis</option>
            <option className="text-slate-950" value="marah">Marah</option>
            <option className="text-slate-950" value="tertarik">Tertarik</option>
            <option className="text-slate-950" value="siap">Siap</option>
            <option className="text-slate-950" value="netral">Netral</option>
          </select>
          <Input placeholder="Keberatan utama" {...form.register("main_objection")} />
          <label className="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" className="h-4 w-4" {...form.register("bridge_candidate")} />
            Bridge candidate
          </label>
          <Button className="w-full" disabled={mutation.isPending}>
            {mutation.isPending ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
            Simpan Cepat
          </Button>
        </form>
      ) : null}

      <a href={item.detailUrl} className="mt-4 inline-flex items-center gap-2 text-sm text-slate-200 hover:text-white">
        Detail lengkap
        <ArrowUpRight className="h-4 w-4" />
      </a>
    </div>
  );
}

function PipelineFilters({
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
  current: Record<string, string>;
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
      className="grid gap-3 md:grid-cols-2 xl:grid-cols-6"
      onSubmit={(event) => {
        event.preventDefault();
        onApply(buildQuery(form));
      }}
    >
      <Input
        value={form.q}
        onChange={(event) => setForm((prev) => ({ ...prev, q: event.target.value }))}
        placeholder="Cari cepat nama / perusahaan / kode"
      />
      <NativeSelect
        value={form.account_category}
        onChange={(value) => setForm((prev) => ({ ...prev, account_category: value }))}
        placeholder="Semua kategori"
        options={accountCategories}
      />
      <NativeSelect
        value={form.owner_id}
        onChange={(value) => setForm((prev) => ({ ...prev, owner_id: value }))}
        placeholder="Semua owner"
        options={salesUsers}
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
        value={form.bridge_status}
        onChange={(value) => setForm((prev) => ({ ...prev, bridge_status: value }))}
        placeholder="Semua bridge status"
        options={bridgeStatuses}
      />
      <NativeSelect
        value={form.follow_up}
        onChange={(value) => setForm((prev) => ({ ...prev, follow_up: value }))}
        placeholder="Semua follow up"
        options={[
          { value: "overdue", label: "Terlambat" },
          { value: "today", label: "Hari Ini" },
          { value: "week", label: "7 Hari" },
        ]}
      />
      <div className="flex gap-3 xl:col-span-2">
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
          value={form.lost_reason}
          onChange={(value) => setForm((prev) => ({ ...prev, lost_reason: value }))}
          placeholder="Semua lost reason"
          options={lostReasons}
        />
        <Button type="submit" variant="secondary">
          Filter Board
        </Button>
      </div>
    </form>
  );
}

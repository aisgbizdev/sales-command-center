import * as React from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Pencil, Save } from "lucide-react";
import { toast } from "sonner";
import { useLocation } from "wouter";

import type { ProspectDetailResponse } from "@/types";
import { fetchJson, sendJson } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ErrorState, LoadingState } from "@/components/app/shared";

export function ProspectDetailPage({ params }: { params: { id: string } }) {
  const queryClient = useQueryClient();
  const [, setLocation] = useLocation();
  const id = params.id;

  const detail = useQuery({
    queryKey: ["prospect", id],
    queryFn: () => fetchJson<ProspectDetailResponse>(`/react-api/prospects/${id}`),
  });

  const [dailyActivityType, setDailyActivityType] = React.useState("");
  const [dailySummary, setDailySummary] = React.useState("");
  const [dailyResult, setDailyResult] = React.useState("");
  const [objectionType, setObjectionType] = React.useState("");
  const [objectionDetail, setObjectionDetail] = React.useState("");
  const [emotionalState, setEmotionalState] = React.useState("");

  React.useEffect(() => {
    if (!detail.data) return;
    setDailyActivityType("");
    setDailySummary("");
    setDailyResult("");
    setObjectionType("");
    setObjectionDetail("");
    setEmotionalState("");
  }, [detail.data?.prospect.id]);

  const logMutation = useMutation({
    mutationFn: async () => {
      if (!detail.data) return { message: "" };
      return sendJson<{ message: string }>(detail.data.storeLogUrl, {
        daily_activity_type: dailyActivityType,
        daily_summary: dailySummary,
        daily_result: dailyResult || null,
        objection_type: objectionType || null,
        objection_detail: objectionDetail || null,
        emotional_state: emotionalState || null,
      });
    },
    onSuccess: async (data) => {
      toast.success(data.message || "Tersimpan.");
      setDailyActivityType("");
      setDailySummary("");
      setDailyResult("");
      setObjectionType("");
      setObjectionDetail("");
      setEmotionalState("");
      await queryClient.invalidateQueries({ queryKey: ["prospect", id] });
      await queryClient.invalidateQueries({ queryKey: ["prospects"] });
      await queryClient.invalidateQueries({ queryKey: ["dashboard"] });
      await queryClient.invalidateQueries({ queryKey: ["pipeline"] });
      await queryClient.invalidateQueries({ queryKey: ["performance"] });
    },
    onError: () => toast.error("Gagal menyimpan input harian."),
  });

  if (detail.isLoading) return <LoadingState label="Memuat detail prospek..." />;
  if (detail.isError || !detail.data) return <ErrorState />;

  const { prospect, logs } = detail.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="grid gap-4 lg:grid-cols-[1.6fr_minmax(320px,1fr)]">
          <div>
            <CardTitle className="text-3xl">
              {prospect.prospectCode} - {prospect.name}
            </CardTitle>
            <CardDescription>
              {prospect.company} | Owner: {prospect.owner}
            </CardDescription>
            <div className="mt-4 flex flex-wrap gap-2">
              <span className="badge info">{prospect.accountCategoryLabel}</span>
              <span className="badge">{prospect.statusLabel}</span>
            </div>
          </div>

          <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.2em] text-[#d9c995]/70">Aksi</p>
            <div className="mt-3 flex flex-wrap gap-3">
              {detail.data.canEdit ? (
                <Button type="button" variant="secondary" onClick={() => setLocation(`/prospects/${prospect.id}/edit`)}>
                  <Pencil className="h-4 w-4" />
                  Edit Data Prospek
                </Button>
              ) : null}
            </div>
            {prospect.notes ? (
              <div className="mt-4 rounded-[18px] border border-white/10 bg-white/[0.04] p-4 text-sm text-[#d9c995]">
                <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Catatan Internal</p>
                <p className="mt-2 whitespace-pre-wrap">{prospect.notes}</p>
              </div>
            ) : null}
          </div>
        </CardHeader>

        <CardContent className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
          <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">Status</p>
            <p className="mt-3 text-2xl font-semibold tracking-[-0.04em]">{prospect.statusLabel}</p>
          </div>
          <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">Kategori Akun</p>
            <p className="mt-3 text-2xl font-semibold tracking-[-0.04em]">{prospect.accountCategoryLabel}</p>
          </div>
          <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">Prioritas</p>
            <p className="mt-3 text-2xl font-semibold tracking-[-0.04em]">{prospect.priority}</p>
          </div>
          <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">Follow Up</p>
            <p className="mt-3 text-2xl font-semibold tracking-[-0.04em]">{prospect.nextFollowUpDateLabel}</p>
          </div>
          <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.16em] text-[#d9c995]/70">Estimasi</p>
            <p className="mt-3 text-2xl font-semibold tracking-[-0.04em]">{prospect.estimationValueLabel}</p>
          </div>
        </CardContent>
      </Card>

      {detail.data.canEdit ? (
        <Card>
          <CardHeader>
            <CardTitle>Input Harian Baru</CardTitle>
            <CardDescription>Catat aktivitas terbaru tanpa harus mengubah semua data prospek.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3">
            <div className="grid gap-3 md:grid-cols-2">
              <label className="grid gap-2 text-sm text-[#d9c995]">
                Jenis Aktivitas
                <select
                  value={dailyActivityType}
                  onChange={(event) => setDailyActivityType(event.target.value)}
                  className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
                >
                  <option value="">Pilih</option>
                  {detail.data.types.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="grid gap-2 text-sm text-[#d9c995]">
                Ringkasan
                <input
                  value={dailySummary}
                  onChange={(event) => setDailySummary(event.target.value)}
                  className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                  placeholder="Contoh: Follow up via telepon..."
                />
              </label>
            </div>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Hasil
              <textarea
                value={dailyResult}
                onChange={(event) => setDailyResult(event.target.value)}
                className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Opsional"
              />
            </label>
            <div className="grid gap-3 md:grid-cols-2">
              <label className="grid gap-2 text-sm text-[#d9c995]">
                Objection Type
                <select
                  value={objectionType}
                  onChange={(event) => setObjectionType(event.target.value)}
                  className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
                >
                  <option value="">Tidak ada objection</option>
                  {detail.data.objectionTypes.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="grid gap-2 text-sm text-[#d9c995]">
                Emotional State
                <select
                  value={emotionalState}
                  onChange={(event) => setEmotionalState(event.target.value)}
                  className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
                >
                  <option value="">Tidak dicatat</option>
                  {detail.data.emotionalStates.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </select>
              </label>
            </div>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Detail Objection
              <textarea
                value={objectionDetail}
                onChange={(event) => setObjectionDetail(event.target.value)}
                className="min-h-[82px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Opsional, contoh: user minta bukti profit member lama."
              />
            </label>
            <div>
              <Button
                type="button"
                disabled={logMutation.isPending || !dailyActivityType || !dailySummary.trim()}
                onClick={() => logMutation.mutate()}
              >
                <Save className="h-4 w-4" />
                Simpan Input Harian
              </Button>
            </div>
          </CardContent>
        </Card>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle>Riwayat Input Harian</CardTitle>
          <CardDescription>Aktivitas follow up yang sudah tercatat untuk prospek ini.</CardDescription>
        </CardHeader>
        <CardContent>
          {logs.length === 0 ? (
            <div className="rounded-[22px] border border-dashed border-white/10 bg-white/5 p-6 text-center text-sm text-[#d9c995]/70">
              Belum ada riwayat aktivitas.
            </div>
          ) : (
            <div className="overflow-x-auto rounded-[22px] border border-white/10">
              <table className="min-w-full border-collapse bg-white/[0.02]">
                <thead>
                  <tr className="bg-white/[0.03]">
                    {["Tanggal", "Tipe", "Ringkasan", "Objection", "Hasil", "User"].map((h) => (
                      <th key={h} className="px-4 py-3 text-left text-[11px] uppercase tracking-[0.18em] text-[#d9c995]">
                        {h}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {logs.map((log) => (
                    <tr key={log.id} className="border-t border-white/10 align-top text-sm text-[#d9c995] odd:bg-transparent even:bg-white/[0.02]">
                      <td className="px-4 py-4">{log.dateLabel}</td>
                      <td className="px-4 py-4">{log.activityTypeLabel}</td>
                      <td className="px-4 py-4">{log.summary}</td>
                      <td className="px-4 py-4">
                        {log.objectionTypeLabel ? (
                          <div className="space-y-2">
                            <Badge variant="warn">{log.objectionTypeLabel}</Badge>
                            {log.emotionalStateLabel ? <p className="text-xs text-[#d9c995]/70">{log.emotionalStateLabel}</p> : null}
                          </div>
                        ) : "-"}
                      </td>
                      <td className="px-4 py-4">{log.result}</td>
                      <td className="px-4 py-4">{log.user}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

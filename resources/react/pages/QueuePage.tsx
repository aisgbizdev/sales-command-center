import * as React from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";
import { toast } from "sonner";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { DataTable, ErrorState, LoadingState, NativeSelect, OverlayModal, movePage, priorityVariant, statusVariant } from "@/components/app/shared";
import { fetchJson, sendJson } from "@/lib/api";
import type { QueueHistoryResponse, QueueResponse } from "@/types";

type QueueActionKind = "done" | "snooze" | "dismiss";

const SNOOZE_OPTIONS = [
  { value: "30m", label: "30 menit" },
  { value: "2h", label: "2 jam" },
  { value: "tomorrow", label: "Besok 09:00" },
];

export function QueuePage() {
  const queryClient = useQueryClient();
  const [location, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const search = React.useMemo(() => new URLSearchParams(queryString), [queryString]);
  const [actionModal, setActionModal] = React.useState<{
    leadId: number;
    leadLabel: string;
    kind: QueueActionKind;
  } | null>(null);
  const [historyLeadId, setHistoryLeadId] = React.useState<number | null>(null);
  const [reasonTag, setReasonTag] = React.useState("");
  const [reasonNote, setReasonNote] = React.useState("");
  const [snoozeDuration, setSnoozeDuration] = React.useState("30m");

  const queue = useQuery({
    queryKey: ["queue", queryString],
    queryFn: () => fetchJson<QueueResponse>(`/react-api/queue${queryString}`),
    refetchInterval: 15000,
  });

  const history = useQuery({
    queryKey: ["queue-history", historyLeadId],
    queryFn: () => fetchJson<QueueHistoryResponse>(`/react-api/queue/${historyLeadId}/history`),
    enabled: historyLeadId !== null,
  });

  const actionMutation = useMutation({
    mutationFn: async () => {
      if (!actionModal) return;
      const payload: Record<string, string> = {
        reason_tag: reasonTag,
      };
      if (reasonNote.trim()) payload.reason_note = reasonNote.trim();
      if (actionModal.kind === "snooze") {
        payload.duration = snoozeDuration;
      }
      await sendJson(`/react-api/queue/${actionModal.leadId}/${actionModal.kind}`, payload);
    },
    onSuccess: async () => {
      toast.success("Queue action tersimpan.");
      setActionModal(null);
      setReasonTag("");
      setReasonNote("");
      setSnoozeDuration("30m");
      await queryClient.invalidateQueries({ queryKey: ["queue"] });
      await queryClient.invalidateQueries({ queryKey: ["queue-history"] });
    },
    onError: () => toast.error("Gagal menyimpan queue action."),
  });

  if (queue.isLoading) return <LoadingState label="Memuat execution queue..." />;
  if (queue.isError || !queue.data) return <ErrorState />;

  const rows = queue.data.items.map((item) => [
    `${item.prospectCode} - ${item.name}`,
    item.owner,
    <Badge key={`${item.leadId}-status`} variant={statusVariant(item.status)}>
      {item.statusLabel}
    </Badge>,
    <div key={`${item.leadId}-score`} className="space-y-1">
      <p className="font-medium text-[#fff2a2]">{item.priorityScore}</p>
      <Badge variant={priorityVariant(item.priorityBand === "p0" ? "critical" : item.priorityBand === "p1" ? "high" : item.priorityBand === "p2" ? "medium" : "normal")}>
        {item.priorityBand.toUpperCase()}
      </Badge>
    </div>,
    `${item.ghostRiskScore}%`,
    item.overdueMinutes > 0 ? `${Math.floor(item.overdueMinutes / 60)}h` : "-",
    <div key={`${item.leadId}-action`} className="space-y-1">
      <p>{item.nextActionLabel}</p>
      {item.aiInsight?.nextActionText ? (
        <p className="text-xs text-cyan-200">AI: {item.aiInsight.nextActionText}</p>
      ) : null}
      <p className="text-xs text-[#d9c995]/70">
        {item.nextActionConfidence ? `${item.nextActionConfidence}%` : "-"} · {item.nextActionExpiresAtLabel ?? "-"}
      </p>
      {item.aiInsight?.confidence !== undefined && item.aiInsight?.confidence !== null ? (
        <p className="text-xs text-[#d9c995]/70">AI confidence: {Math.round(item.aiInsight.confidence * 100)}%</p>
      ) : null}
    </div>,
    <div key={`${item.leadId}-ctl`} className="flex flex-wrap gap-2">
      <a href={item.detailUrl} className="rounded-xl border border-white/10 px-2 py-1 text-xs hover:bg-white/5">
        Chat
      </a>
      <button
        type="button"
        className="rounded-xl border border-emerald-400/30 px-2 py-1 text-xs text-emerald-200 hover:bg-emerald-500/10"
        onClick={() => setActionModal({ leadId: item.leadId, leadLabel: `${item.prospectCode} - ${item.name}`, kind: "done" })}
      >
        Done
      </button>
      <button
        type="button"
        className="rounded-xl border border-amber-400/30 px-2 py-1 text-xs text-amber-200 hover:bg-amber-500/10"
        onClick={() => setActionModal({ leadId: item.leadId, leadLabel: `${item.prospectCode} - ${item.name}`, kind: "snooze" })}
      >
        Snooze
      </button>
      <button
        type="button"
        className="rounded-xl border border-rose-400/30 px-2 py-1 text-xs text-rose-200 hover:bg-rose-500/10"
        onClick={() => setActionModal({ leadId: item.leadId, leadLabel: `${item.prospectCode} - ${item.name}`, kind: "dismiss" })}
      >
        Dismiss
      </button>
      <button
        type="button"
        className="rounded-xl border border-white/10 px-2 py-1 text-xs hover:bg-white/5"
        onClick={() => setHistoryLeadId(item.leadId)}
      >
        History
      </button>
    </div>,
  ]);

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-3xl">Execution Queue</CardTitle>
          <CardDescription>Work this now. Bukan report, ini list eksekusi aktif.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-4">
          <NativeSelect
            value={queue.data.filters.current.sort || "priority"}
            onChange={(value) => {
              const next = new URLSearchParams(search);
              next.set("sort", value || "priority");
              next.delete("page");
              setLocation(`/queue?${next.toString()}`);
            }}
            placeholder="Sort"
            options={[
              { value: "priority", label: "Priority Score" },
              { value: "sla", label: "SLA Risk" },
              { value: "fresh", label: "Action Expiry" },
            ]}
          />
          <NativeSelect
            value={queue.data.filters.current.priority_band}
            onChange={(value) => {
              const next = new URLSearchParams(search);
              if (value) next.set("priority_band", value); else next.delete("priority_band");
              next.delete("page");
              setLocation(`/queue?${next.toString()}`);
            }}
            placeholder="Semua Priority Band"
            options={[
              { value: "p0", label: "P0" },
              { value: "p1", label: "P1" },
              { value: "p2", label: "P2" },
              { value: "p3", label: "P3" },
            ]}
          />
          <NativeSelect
            value={queue.data.filters.current.ghost_risk}
            onChange={(value) => {
              const next = new URLSearchParams(search);
              if (value) next.set("ghost_risk", value); else next.delete("ghost_risk");
              next.delete("page");
              setLocation(`/queue?${next.toString()}`);
            }}
            placeholder="Ghost Risk"
            options={[{ value: "1", label: "Ghost Risk Only" }]}
          />
          <NativeSelect
            value={queue.data.filters.current.overdue_only}
            onChange={(value) => {
              const next = new URLSearchParams(search);
              if (value) next.set("overdue_only", value); else next.delete("overdue_only");
              next.delete("page");
              setLocation(`/queue?${next.toString()}`);
            }}
            placeholder="Overdue"
            options={[{ value: "1", label: "Overdue Only" }]}
          />
        </CardContent>
      </Card>

      <Card>
        <CardContent>
          <DataTable
            headers={["Lead", "Owner", "Stage", "Score", "Ghost", "Overdue", "Next Action", "Aksi"]}
            rows={rows}
            emptyMessage="Queue kosong untuk filter ini."
          />

          <div className="mt-4 flex items-center justify-between text-sm text-[#d9c995]">
            <p>
              Page {queue.data.meta.currentPage} / {queue.data.meta.lastPage} · {queue.data.meta.total} items
            </p>
            <div className="flex gap-2">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                disabled={queue.data.meta.currentPage <= 1}
                onClick={() => movePage(location, setLocation, queue.data.meta.currentPage - 1)}
              >
                Prev
              </Button>
              <Button
                type="button"
                variant="secondary"
                size="sm"
                disabled={queue.data.meta.currentPage >= queue.data.meta.lastPage}
                onClick={() => movePage(location, setLocation, queue.data.meta.currentPage + 1)}
              >
                Next
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <OverlayModal open={actionModal !== null} onClose={() => setActionModal(null)} title="Queue Action" subtitle="Lifecycle">
        <div className="space-y-3">
          <p className="text-sm text-[#d9c995]">{actionModal?.leadLabel}</p>
          {actionModal?.kind === "snooze" ? (
            <NativeSelect
              value={snoozeDuration}
              onChange={setSnoozeDuration}
              placeholder="Durasi Snooze"
              options={SNOOZE_OPTIONS}
            />
          ) : null}
          <NativeSelect
            value={reasonTag}
            onChange={setReasonTag}
            placeholder="Reason Tag"
            options={queue.data.filters.reasonTags}
          />
          <textarea
            value={reasonNote}
            onChange={(event) => setReasonNote(event.target.value)}
            className="min-h-[90px] w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
            placeholder="Catatan singkat (opsional)"
          />
          <Button
            type="button"
            disabled={actionMutation.isPending || !reasonTag}
            onClick={() => actionMutation.mutate()}
          >
            Simpan
          </Button>
        </div>
      </OverlayModal>

      <OverlayModal open={historyLeadId !== null} onClose={() => setHistoryLeadId(null)} title="Action History" subtitle="Queue">
        {history.isLoading ? (
          <p className="text-sm text-[#d9c995]">Memuat history...</p>
        ) : history.data && history.data.items.length > 0 ? (
          <div className="space-y-2">
            {history.data.items.map((item) => (
              <div key={item.id} className="rounded-xl border border-white/10 bg-white/5 p-3 text-sm">
                <p className="font-medium text-[#fff2a2]">{item.actionType.toUpperCase()} · {item.reasonTag ?? "-"}</p>
                <p className="text-[#d9c995]">{item.reasonNote || "-"}</p>
                <p className="text-xs text-[#d9c995]/70">{item.actedAtLabel}</p>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-[#d9c995]">Belum ada history action.</p>
        )}
      </OverlayModal>
    </div>
  );
}

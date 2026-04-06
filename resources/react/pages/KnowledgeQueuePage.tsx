import * as React from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { BookOpenCheck, CheckCheck, Search, ShieldCheck, XCircle } from "lucide-react";
import { toast } from "sonner";
import { useLocation } from "wouter";

import type { KnowledgeQueueResponse, Option } from "@/types";
import { fetchJson, sendJson } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { ErrorState, LoadingState, buildQuery, getQueryString, movePage, NativeSelect } from "@/components/app/shared";

export function KnowledgeQueuePage() {
  const [location, setLocation] = useLocation();
  const queryString = getQueryString(location);

  const queue = useQuery({
    queryKey: ["knowledge-queue", queryString],
    queryFn: () => fetchJson<KnowledgeQueueResponse>(`/react-api/knowledge-queue${queryString}`),
  });

  if (queue.isLoading) return <LoadingState label="Memuat antrian pengetahuan..." />;
  if (queue.isError || !queue.data) return <ErrorState />;

  const { items, meta, filters } = queue.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-3xl">Antrian Pengetahuan</CardTitle>
          <CardDescription>Daftar pola obrolan penting yang diajukan tim untuk diperiksa dan, bila perlu, disetujui Super Admin.</CardDescription>
        </CardHeader>
        <CardContent>
          <KnowledgeQueueFilters
            current={filters.current}
            statuses={filters.statuses}
            priorities={filters.priorities}
            accountCategories={filters.accountCategories}
            onApply={(params) => setLocation(`/knowledge-queue${params ? `?${params}` : ""}`)}
          />
        </CardContent>
      </Card>

      <div className="grid gap-4 xl:grid-cols-2">
        {items.length === 0 ? (
          <Card className="xl:col-span-2">
            <CardContent className="py-14 text-center text-slate-400">Belum ada item knowledge queue pada filter ini.</CardContent>
          </Card>
        ) : (
          items.map((item) => <KnowledgeQueueCard key={item.id} item={item} />)
        )}
      </div>

      <div className="flex items-center justify-between rounded-[20px] border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-400">
        <span>
          Halaman {meta.currentPage} dari {meta.lastPage}
        </span>
        <div className="flex gap-2">
          <Button variant="secondary" size="sm" disabled={meta.currentPage <= 1} onClick={() => movePage(location, setLocation, meta.currentPage - 1)}>
            Prev
          </Button>
          <Button variant="secondary" size="sm" disabled={meta.currentPage >= meta.lastPage} onClick={() => movePage(location, setLocation, meta.currentPage + 1)}>
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}

function KnowledgeQueueFilters({
  current,
  statuses,
  priorities,
  accountCategories,
  onApply,
}: {
  current: Record<string, string>;
  statuses: Option[];
  priorities: Option[];
  accountCategories: Option[];
  onApply: (params: string) => void;
}) {
  const [form, setForm] = React.useState({
    status: current.status ?? "",
    priority: current.priority ?? "",
    account_category: current.account_category ?? "",
  });

  return (
    <form
      className="grid gap-3 md:grid-cols-2 xl:grid-cols-4"
      onSubmit={(event) => {
        event.preventDefault();
        onApply(buildQuery(form));
      }}
    >
      <NativeSelect value={form.status} onChange={(value) => setForm((prev) => ({ ...prev, status: value }))} placeholder="Semua status" options={statuses} />
      <NativeSelect value={form.priority} onChange={(value) => setForm((prev) => ({ ...prev, priority: value }))} placeholder="Semua prioritas" options={priorities} />
      <NativeSelect value={form.account_category} onChange={(value) => setForm((prev) => ({ ...prev, account_category: value }))} placeholder="Semua kategori" options={accountCategories} />
      <Button type="submit" variant="secondary">
        <Search className="h-4 w-4" />
        Filter
      </Button>
    </form>
  );
}

function KnowledgeQueueCard({
  item,
}: {
  item: KnowledgeQueueResponse["items"][number];
}) {
  const queryClient = useQueryClient();
  const [reviewNote, setReviewNote] = React.useState(item.superAdminNote ?? "");

  const refresh = async () => {
    await queryClient.invalidateQueries({ queryKey: ["knowledge-queue"] });
    await queryClient.invalidateQueries({ queryKey: ["chat-reviews"] });
  };

  const setReviewMutation = useMutation({
    mutationFn: () => sendJson<{ message: string }>(item.setReviewUrl, {}, "PATCH"),
    onSuccess: async (data) => {
      toast.success(data.message);
      await refresh();
    },
    onError: () => toast.error("Gagal memindahkan item ke tahap review."),
  });

  const approveMutation = useMutation({
    mutationFn: () => sendJson<{ message: string }>(item.approveUrl, { super_admin_note: reviewNote }, "PATCH"),
    onSuccess: async (data) => {
      toast.success(data.message);
      await refresh();
    },
    onError: () => toast.error("Approve gagal diproses."),
  });

  const rejectMutation = useMutation({
    mutationFn: () => sendJson<{ message: string }>(item.rejectUrl, { super_admin_note: reviewNote }, "PATCH"),
    onSuccess: async (data) => {
      toast.success(data.message);
      await refresh();
    },
    onError: () => toast.error("Reject gagal diproses."),
  });

  return (
    <Card>
      <CardHeader>
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <CardTitle>{item.chatReviewTitle}</CardTitle>
            <CardDescription className="mt-2">
              {item.prospectName} ({item.prospectCode}) • {item.accountCategoryLabel}
            </CardDescription>
          </div>
          <div className="flex flex-wrap gap-2">
            <Badge variant={item.priority === "urgent" ? "danger" : item.priority === "high" ? "warn" : "info"}>{item.priorityLabel}</Badge>
            <Badge variant={item.status === "approved" ? "success" : item.status === "rejected" ? "danger" : "warn"}>{item.statusLabel}</Badge>
          </div>
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid gap-3 rounded-[20px] border border-white/10 bg-white/5 p-4 text-sm text-slate-300 md:grid-cols-2">
          <div>
            <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Requester</p>
            <p className="mt-2">{item.requester}</p>
            <p className="text-xs text-slate-500">Reviewer: {item.reviewer}</p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Chat Review</p>
            <p className="mt-2">Outcome: {item.chatReviewOutcome}</p>
            <p className="text-xs text-slate-500">Status chat review: {item.chatReviewStatus}</p>
          </div>
        </div>

        <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
          <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Problem Pattern</p>
          <p className="mt-3 text-sm leading-7 text-slate-300">{item.problemPattern}</p>
        </div>

        <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
          <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Recommended Update</p>
          <p className="mt-3 text-sm leading-7 text-slate-300">{item.recommendedUpdate}</p>
          {item.expectedImpact ? <p className="mt-3 text-sm text-slate-400">Impact: {item.expectedImpact}</p> : null}
          <p className="mt-3 text-xs text-slate-500">Reviewed at: {item.reviewedAtLabel}</p>
        </div>

        {item.canReview ? (
          <div className="grid gap-3 rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-slate-500">Aksi Super Admin</p>
            <Input value={reviewNote} onChange={(event) => setReviewNote(event.target.value)} placeholder="Catatan review admin" />
            <div className="flex flex-wrap gap-3">
              <Button variant="secondary" onClick={() => setReviewMutation.mutate()} disabled={setReviewMutation.isPending}>
                <ShieldCheck className="h-4 w-4" />
                Set In Review
              </Button>
              <Button onClick={() => approveMutation.mutate()} disabled={approveMutation.isPending}>
                <CheckCheck className="h-4 w-4" />
                Approve
              </Button>
              <Button variant="danger" onClick={() => rejectMutation.mutate()} disabled={rejectMutation.isPending || !reviewNote.trim()}>
                <XCircle className="h-4 w-4" />
                Reject
              </Button>
            </div>
          </div>
        ) : (
          <div className="flex items-center gap-2 rounded-[20px] border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
            <BookOpenCheck className="h-4 w-4" />
            Role ini hanya bisa melihat antrian.
          </div>
        )}
      </CardContent>
    </Card>
  );
}

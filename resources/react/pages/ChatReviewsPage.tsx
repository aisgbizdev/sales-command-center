import * as React from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { MessageSquareQuote, Search, Send, Sparkles } from "lucide-react";
import { toast } from "sonner";
import { useLocation } from "wouter";
import { useSearch } from "wouter/use-browser-location";

import type { ChatReviewsResponse, Option } from "@/types";
import { fetchJson, sendJson } from "@/lib/api";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { ErrorState, LoadingState, OverlayModal, buildQuery, movePage, NativeSelect } from "@/components/app/shared";

const noteTags: Option[] = [
  { value: "general", label: "General" },
  { value: "win_pattern", label: "Win Pattern" },
  { value: "loss_pattern", label: "Loss Pattern" },
  { value: "coaching", label: "Coaching" },
  { value: "gpt_update", label: "GPT Update" },
];

const queuePriorities: Option[] = [
  { value: "low", label: "Low" },
  { value: "normal", label: "Normal" },
  { value: "high", label: "High" },
  { value: "urgent", label: "Urgent" },
];

export function ChatReviewsPage() {
  const [location, setLocation] = useLocation();
  const queryString = useSearch() ?? "";
  const [filtersOpen, setFiltersOpen] = React.useState(false);

  const reviews = useQuery({
    queryKey: ["chat-reviews", queryString],
    queryFn: () => fetchJson<ChatReviewsResponse>(`/react-api/chat-reviews${queryString}`),
  });

  if (reviews.isLoading) return <LoadingState label="Memuat tinjauan obrolan..." />;
  if (reviews.isError || !reviews.data) return <ErrorState />;

  const { items, meta, filters, permissions } = reviews.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <CardTitle className="text-3xl">Tinjauan Obrolan</CardTitle>
            <CardDescription>Daftar chat yang perlu dibaca ulang, dikomentari manajer, lalu didorong ke knowledge queue bila penting.</CardDescription>
          </div>
          {permissions.canCreateChatReview ? (
            <a href={permissions.createUrl}>
              <Button>
                <MessageSquareQuote className="h-4 w-4" />
                Tambah Review
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

      <OverlayModal open={filtersOpen} title="Filter Tinjauan Obrolan" onClose={() => setFiltersOpen(false)}>
        <ChatReviewFilters
          current={filters.current}
          outcomes={filters.outcomes}
          statuses={filters.statuses}
          accountCategories={filters.accountCategories}
          onApply={(params) => {
            setLocation(`/chat-reviews${params ? `?${params}` : ""}`);
            setFiltersOpen(false);
          }}
        />
      </OverlayModal>

      <div className="grid gap-4 xl:grid-cols-2">
        {items.length === 0 ? (
          <Card className="xl:col-span-2">
            <CardContent className="py-14 text-center text-[#d9c995]">Belum ada review obrolan yang cocok dengan filter ini.</CardContent>
          </Card>
        ) : (
          items.map((item) => <ChatReviewCard key={item.id} item={item} />)
        )}
      </div>

      <div className="flex items-center justify-between rounded-[20px] border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#d9c995]">
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

function ChatReviewFilters({
  current,
  outcomes,
  statuses,
  accountCategories,
  onApply,
}: {
  current: Record<string, string>;
  outcomes: Option[];
  statuses: Option[];
  accountCategories: Option[];
  onApply: (params: string) => void;
}) {
  const [form, setForm] = React.useState({
    q: current.q ?? "",
    outcome: current.outcome ?? "",
    status: current.status ?? "",
    account_category: current.account_category ?? "",
  });

	  return (
	    <form
	      className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
	      onSubmit={(event) => {
	        event.preventDefault();
	        onApply(buildQuery(form));
	      }}
	    >
	      <Input className="sm:col-span-2 lg:col-span-3" value={form.q} onChange={(event) => setForm((prev) => ({ ...prev, q: event.target.value }))} placeholder="Cari judul, customer, atau perusahaan" />
	      <NativeSelect value={form.outcome} onChange={(value) => setForm((prev) => ({ ...prev, outcome: value }))} placeholder="Semua outcome" options={outcomes} />
	      <NativeSelect value={form.status} onChange={(value) => setForm((prev) => ({ ...prev, status: value }))} placeholder="Semua status" options={statuses} />
	      <NativeSelect value={form.account_category} onChange={(value) => setForm((prev) => ({ ...prev, account_category: value }))} placeholder="Semua kategori" options={accountCategories} />
	      <Button type="submit" variant="secondary" className="sm:col-span-2 lg:col-span-3">
	        <Search className="h-4 w-4" />
	        Filter
	      </Button>
	    </form>
	  );
	}

function ChatReviewCard({
  item,
}: {
  item: ChatReviewsResponse["items"][number];
}) {
  const queryClient = useQueryClient();
  const [noteTag, setNoteTag] = React.useState("general");
  const [note, setNote] = React.useState("");
  const [priority, setPriority] = React.useState("normal");
  const [problemPattern, setProblemPattern] = React.useState("");
  const [recommendedUpdate, setRecommendedUpdate] = React.useState(item.suggestedKnowledgeUpdate ?? "");
  const [expectedImpact, setExpectedImpact] = React.useState("");

  const noteMutation = useMutation({
    mutationFn: () => sendJson<{ message: string }>(item.addManagerNoteUrl, { tag: noteTag, note }),
    onSuccess: async (data) => {
      toast.success(data.message);
      setNote("");
      await queryClient.invalidateQueries({ queryKey: ["chat-reviews"] });
      await queryClient.invalidateQueries({ queryKey: ["knowledge-queue"] });
    },
    onError: () => toast.error("Catatan manajer gagal dikirim."),
  });

  const queueMutation = useMutation({
    mutationFn: () =>
      sendJson<{ message: string }>(item.markImportantUrl, {
        priority,
        problem_pattern: problemPattern,
        recommended_update: recommendedUpdate,
        expected_impact: expectedImpact,
      }),
    onSuccess: async (data) => {
      toast.success(data.message);
      setProblemPattern("");
      setExpectedImpact("");
      await queryClient.invalidateQueries({ queryKey: ["chat-reviews"] });
      await queryClient.invalidateQueries({ queryKey: ["knowledge-queue"] });
    },
    onError: () => toast.error("Gagal memasukkan ke antrian pengetahuan."),
  });

  return (
    <Card>
      <CardHeader>
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <CardTitle>{item.title}</CardTitle>
            <CardDescription className="mt-2">
              {item.customerName} • {item.customerCompany} • {item.prospectName} ({item.prospectCode})
            </CardDescription>
          </div>
          <div className="flex flex-wrap gap-2">
            <Badge variant="info">{item.accountCategoryLabel}</Badge>
            <Badge variant={item.outcome === "berhasil" ? "success" : item.outcome === "gagal" ? "danger" : "warn"}>{item.outcomeLabel}</Badge>
            {item.objectionTypeLabel ? <Badge variant="warn">{item.objectionTypeLabel}</Badge> : null}
            {item.emotionalStateLabel ? <Badge variant="orange">{item.emotionalStateLabel}</Badge> : null}
            <Badge variant="warn">{item.statusLabel}</Badge>
          </div>
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid gap-3 rounded-[20px] border border-white/10 bg-white/5 p-4 text-sm text-[#d9c995] md:grid-cols-2">
          <div>
            <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Submitter</p>
            <p className="mt-2">{item.submitter}</p>
            <p className="text-xs text-[#d9c995]/70">{item.submitterRole}</p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Statistik</p>
            <p className="mt-2">Catatan manajer: {item.managerNotesCount}</p>
            <p className="text-xs text-[#d9c995]/70">Masuk knowledge queue: {item.knowledgeQueueCount}</p>
          </div>
        </div>

        <div className="rounded-[20px] border border-white/10 bg-white/5 p-4">
          <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Ringkasan</p>
          <p className="mt-3 text-sm leading-7 text-[#d9c995]">{item.summary}</p>
        </div>

        {item.canComment ? (
          <div className="grid gap-3 rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Catatan Manajer</p>
            <div className="grid gap-3 md:grid-cols-[220px_minmax(0,1fr)_auto]">
              <NativeSelect value={noteTag} onChange={setNoteTag} placeholder="Tag catatan" options={noteTags} />
              <textarea
                value={note}
                onChange={(event) => setNote(event.target.value)}
                className="min-h-[44px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Tambahkan insight, coaching note, atau pola baru."
              />
              <Button disabled={noteMutation.isPending || !note.trim()} onClick={() => noteMutation.mutate()}>
                <Send className="h-4 w-4" />
                Kirim
              </Button>
            </div>
          </div>
        ) : null}

        {item.canMarkImportant ? (
          <div className="grid gap-3 rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Dorong ke Knowledge Queue</p>
            <div className="grid gap-3 md:grid-cols-2">
              <NativeSelect value={priority} onChange={setPriority} placeholder="Priority" options={queuePriorities} />
              <Input value={problemPattern} onChange={(event) => setProblemPattern(event.target.value)} placeholder="Problem pattern utama" />
              <textarea
                value={recommendedUpdate}
                onChange={(event) => setRecommendedUpdate(event.target.value)}
                className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5 md:col-span-2"
                placeholder="Usulan update knowledge / prompt / playbook."
              />
              <textarea
                value={expectedImpact}
                onChange={(event) => setExpectedImpact(event.target.value)}
                className="min-h-[72px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5 md:col-span-2"
                placeholder="Expected impact opsional."
              />
            </div>
            <div className="flex items-center justify-between gap-3">
              <a href={item.showUrl} className="text-sm text-[#d9c995] underline-offset-4 hover:text-[#ffe37b] hover:underline">
                Lihat detail lengkap
              </a>
              <Button
                disabled={queueMutation.isPending || !problemPattern.trim() || !recommendedUpdate.trim()}
                onClick={() => queueMutation.mutate()}
              >
                <Sparkles className="h-4 w-4" />
                Tandai Penting
              </Button>
            </div>
          </div>
        ) : null}
      </CardContent>
    </Card>
  );
}

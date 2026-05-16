import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, Save } from "lucide-react";
import { useLocation } from "wouter";

import { boot, fetchJson } from "@/lib/api";
import type { ChatReviewDetailResponse, ChatReviewFormResponse } from "@/types";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ErrorState, LoadingState } from "@/components/app/shared";

function SelectField({
  name,
  label,
  options,
  defaultValue,
  placeholder = "-",
  required,
}: {
  name: string;
  label: string;
  options: { value: string; label: string }[];
  defaultValue?: string;
  placeholder?: string;
  required?: boolean;
}) {
  return (
    <label className="grid gap-2 text-sm text-slate-200">
      {label}
      <select
        name={name}
        defaultValue={defaultValue ?? ""}
        required={required}
        className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
      >
        <option value="">{placeholder}</option>
        {options.map((item) => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))}
      </select>
    </label>
  );
}

export function ChatReviewCreatePage() {
  return <ChatReviewForm mode="create" />;
}

export function ChatReviewEditPage({ params }: { params: { id: string } }) {
  return <ChatReviewForm mode="edit" id={params.id} />;
}

function ChatReviewForm({ mode, id }: { mode: "create" | "edit"; id?: string }) {
  const [, setLocation] = useLocation();

  const formData = useQuery({
    queryKey: ["chat-reviews", "form"],
    queryFn: () => fetchJson<ChatReviewFormResponse>("/react-api/chat-reviews/form"),
  });

  const detail = useQuery({
    queryKey: ["chat-review", id],
    enabled: mode === "edit" && Boolean(id),
    queryFn: () => fetchJson<ChatReviewDetailResponse>(`/react-api/chat-reviews/${id}`),
  });

  if (formData.isLoading || (mode === "edit" && detail.isLoading)) {
    return <LoadingState label="Memuat form review..." />;
  }

  if (formData.isError || !formData.data || (mode === "edit" && (detail.isError || !detail.data))) {
    return <ErrorState />;
  }

  const data = formData.data;
  const current = detail.data?.review;

  const action = mode === "create" ? "/chat-reviews" : `/chat-reviews/${current?.id}`;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <CardTitle className="text-3xl">{mode === "create" ? "Tambah Review Obrolan" : "Edit Review Obrolan"}</CardTitle>
            <CardDescription>Catat ringkasan chat yang penting untuk pembelajaran tim.</CardDescription>
          </div>
          <Button type="button" variant="secondary" onClick={() => setLocation("/chat-reviews")}>
            <ArrowLeft className="h-4 w-4" />
            Kembali
          </Button>
        </CardHeader>
      </Card>

      <form method="post" action={action} className="space-y-4">
        <input type="hidden" name="_token" value={boot.csrfToken} />
        {mode === "edit" ? <input type="hidden" name="_method" value="put" /> : null}

        <Card>
          <CardHeader>
            <CardTitle>Detail Kasus</CardTitle>
            <CardDescription>Judul, kanal, outcome, dan relasi prospek.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <label className="grid gap-2 text-sm text-slate-200 sm:col-span-2 lg:col-span-3">
              Judul Kasus
              <input
                name="title"
                defaultValue={current?.title ?? ""}
                required
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            <SelectField name="channel" label="Kanal Obrolan" options={data.channels} defaultValue={current?.channel ?? "whatsapp"} required />
            <SelectField name="outcome" label="Outcome" options={data.outcomes} defaultValue={current?.outcome ?? "netral"} required />
            {mode === "edit" ? (
              <SelectField name="status" label="Status" options={data.statuses} defaultValue={current?.status ?? "draft"} required />
            ) : null}
            <label className="grid gap-2 text-sm text-slate-200">
              Nama Customer
              <input
                name="customer_name"
                defaultValue={current?.customerName ?? ""}
                required
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            <label className="grid gap-2 text-sm text-slate-200">
              Perusahaan
              <input
                name="customer_company"
                defaultValue={current?.customerCompany ?? ""}
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            <SelectField name="prospect_id" label="Prospek Terkait" options={data.prospects} defaultValue={current?.prospectId ?? ""} placeholder="- Tidak terkait -" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Isi Review</CardTitle>
            <CardDescription>Ringkasan chat dan catatan pembelajaran.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3">
            <label className="grid gap-2 text-sm text-slate-200">
              Ringkasan Obrolan
              <textarea
                name="chat_summary"
                defaultValue={current?.chatSummary ?? ""}
                required
                className="min-h-[120px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            <label className="grid gap-2 text-sm text-slate-200">
              Potongan Chat Penting
              <textarea
                name="chat_excerpt"
                defaultValue={current?.chatExcerpt ?? ""}
                className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            <div className="grid gap-3 md:grid-cols-2">
              <label className="grid gap-2 text-sm text-slate-200">
                Yang Berhasil
                <textarea
                  name="what_worked"
                  defaultValue={current?.whatWorked ?? ""}
                  className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                />
              </label>
              <label className="grid gap-2 text-sm text-slate-200">
                Yang Gagal
                <textarea
                  name="what_failed"
                  defaultValue={current?.whatFailed ?? ""}
                  className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                />
              </label>
            </div>
            <label className="grid gap-2 text-sm text-slate-200">
              Saran Pembaruan Pengetahuan GPT
              <textarea
                name="suggested_knowledge_update"
                defaultValue={current?.suggestedKnowledgeUpdate ?? ""}
                className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-slate-500 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
          </CardContent>
        </Card>

        <div className="flex items-center justify-end gap-3">
          <Button type="submit">
            <Save className="h-4 w-4" />
            Simpan
          </Button>
        </div>
      </form>
    </div>
  );
}


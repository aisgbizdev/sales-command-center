import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { Pencil } from "lucide-react";
import { useLocation } from "wouter";

import type { ChatReviewDetailResponse } from "@/types";
import { fetchJson } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ErrorState, LoadingState } from "@/components/app/shared";

export function ChatReviewDetailPage({ params }: { params: { id: string } }) {
  const [, setLocation] = useLocation();
  const id = params.id;

  const detail = useQuery({
    queryKey: ["chat-review", id],
    queryFn: () => fetchJson<ChatReviewDetailResponse>(`/react-api/chat-reviews/${id}`),
  });

  if (detail.isLoading) return <LoadingState label="Memuat detail review..." />;
  if (detail.isError || !detail.data) return <ErrorState />;

  const { review } = detail.data;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="grid gap-4 lg:grid-cols-[1.6fr_minmax(320px,1fr)]">
          <div>
            <CardTitle className="text-3xl">{review.title}</CardTitle>
            <CardDescription>
              {review.customerName}
              {review.customerCompany ? ` - ${review.customerCompany}` : ""}
            </CardDescription>
            <div className="mt-4 flex flex-wrap gap-2">
              <span className="badge info">{review.outcome.toUpperCase()}</span>
              <span className="badge">{review.status.toUpperCase()}</span>
              <span className="badge">{review.channel.toUpperCase()}</span>
            </div>
          </div>

          <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.2em] text-[#d9c995]/70">Aksi</p>
            <div className="mt-3 flex flex-wrap gap-3">
              {detail.data.canEdit ? (
                <Button type="button" variant="secondary" onClick={() => setLocation(`/chat-reviews/${review.id}/edit`)}>
                  <Pencil className="h-4 w-4" />
                  Edit Review
                </Button>
              ) : null}
            </div>
            <div className="mt-4 rounded-[18px] border border-white/10 bg-white/[0.04] p-4 text-sm text-[#d9c995]">
              <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Pengirim</p>
              <p className="mt-2">{review.submitter}</p>
              <p className="text-xs text-[#d9c995]/70">{review.submitterRole}</p>
            </div>
          </div>
        </CardHeader>

        <CardContent className="space-y-3">
          <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Ringkasan</p>
            <p className="mt-3 whitespace-pre-wrap text-sm text-[#d9c995]">{review.chatSummary}</p>
          </div>

          {review.chatExcerpt ? (
            <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
              <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Potongan Chat</p>
              <p className="mt-3 whitespace-pre-wrap text-sm text-[#d9c995]">{review.chatExcerpt}</p>
            </div>
          ) : null}

          <div className="grid gap-3 md:grid-cols-2">
            <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
              <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Yang Berhasil</p>
              <p className="mt-3 whitespace-pre-wrap text-sm text-[#d9c995]">{review.whatWorked || "-"}</p>
            </div>
            <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
              <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Yang Gagal</p>
              <p className="mt-3 whitespace-pre-wrap text-sm text-[#d9c995]">{review.whatFailed || "-"}</p>
            </div>
          </div>

          {review.suggestedKnowledgeUpdate ? (
            <div className="rounded-[22px] border border-white/10 bg-white/5 p-4">
              <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">Saran Update Pengetahuan GPT</p>
              <p className="mt-3 whitespace-pre-wrap text-sm text-[#d9c995]">{review.suggestedKnowledgeUpdate}</p>
            </div>
          ) : null}
        </CardContent>
      </Card>
    </div>
  );
}


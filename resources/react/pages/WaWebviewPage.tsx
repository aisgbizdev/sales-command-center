import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ErrorState, LoadingState } from "@/components/app/shared";
import { sendJson } from "@/lib/api";

type HealthResponse = {
  ok: boolean;
  ready: boolean;
  hasQr: boolean;
  userId: string;
};

type QrResponse = {
  ready: boolean;
  qr: string | null;
  userId: string;
};

type ChatsResponse = {
  items: {
    id: string;
    name: string;
    unreadCount: number;
    timestamp: number | null;
  }[];
};

type MessagesResponse = {
  items: {
    id: string;
    fromMe: boolean;
    body: string;
    timestamp: number | null;
  }[];
};

async function fetchProxy<T>(path: string): Promise<T> {
  const response = await fetch(`/wa-webjs-api${path}`, {
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  if (!response.ok) {
    const raw = await response.text();
    throw new Error(raw || `HTTP ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export function WaWebviewPage() {
  const [selectedChatId, setSelectedChatId] = React.useState<string>("");
  const [to, setTo] = React.useState("");
  const [message, setMessage] = React.useState("");
  const [sendResult, setSendResult] = React.useState("");

  const health = useQuery({
    queryKey: ["wa-webview-health"],
    queryFn: () => fetchProxy<HealthResponse>("/health"),
    refetchInterval: 3000,
  });

  const qr = useQuery({
    queryKey: ["wa-webview-qr"],
    queryFn: () => fetchProxy<QrResponse>("/qr"),
    refetchInterval: 3000,
    enabled: !!health.data && !health.data.ready,
  });

  const chats = useQuery({
    queryKey: ["wa-webview-chats"],
    queryFn: () => fetchProxy<ChatsResponse>("/chats"),
    refetchInterval: health.data?.ready ? 5000 : false,
    enabled: !!health.data?.ready,
  });

  const messages = useQuery({
    queryKey: ["wa-webview-messages", selectedChatId],
    queryFn: () => fetchProxy<MessagesResponse>(`/messages/${encodeURIComponent(selectedChatId)}`),
    refetchInterval: selectedChatId ? 2500 : false,
    enabled: !!selectedChatId && !!health.data?.ready,
  });

  const onSend = async () => {
    const target = to.trim();
    const body = message.trim();
    if (!target || !body) {
      toast.error("To dan message wajib diisi.");
      return;
    }

    try {
      const result = await sendJson<{ success: boolean; id?: string; message?: string }>(
        "/wa-webjs-api/send",
        { to: target, message: body },
      );
      setSendResult(result.id ? `Sent: ${result.id}` : "Sent");
      setMessage("");
      messages.refetch();
    } catch (error) {
      const text = error instanceof Error ? error.message : "Failed to send message.";
      setSendResult(`Error: ${text}`);
    }
  };

  if (health.isLoading) return <LoadingState label="Memuat WA WebView..." />;
  if (health.isError || !health.data) return <ErrorState />;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-3xl">WhatsApp WebJS</CardTitle>
          <CardDescription>Tampilan ini sudah satu pola React dengan modul operasional lain.</CardDescription>
        </CardHeader>
        <CardContent>
          <Button type="button" onClick={() => {
            health.refetch();
            qr.refetch();
            chats.refetch();
            if (selectedChatId) messages.refetch();
          }}>
            Refresh State
          </Button>
        </CardContent>
      </Card>

      <div className="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
        <Card>
          <CardHeader>
            <CardTitle>Session</CardTitle>
            <CardDescription>
              {health.data.ready ? "Status: Connected" : health.data.hasQr ? "Status: Menunggu scan QR" : "Status: Starting..."}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {!health.data.ready ? (
              qr.data?.qr ? (
                <img src={qr.data.qr} alt="QR" className="h-[280px] w-[280px] rounded-xl bg-white object-contain" />
              ) : (
                <div className="h-[280px] w-[280px] rounded-xl border border-dashed border-white/20 bg-white/5" />
              )
            ) : (
              <div className="rounded-xl border border-white/10 bg-white/5 p-3 text-sm text-[#d9c995]">Session ready untuk user ini.</div>
            )}
            <p className="text-xs text-[#d9c995]">Jika belum ready, scan QR dengan WhatsApp di HP.</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Chats</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {!health.data.ready ? (
              <p className="text-sm text-[#d9c995]">Client belum ready.</p>
            ) : chats.data?.items?.length ? (
              <div className="max-h-[420px] space-y-2 overflow-auto">
                {chats.data.items.map((chat) => (
                  <button
                    key={chat.id}
                    type="button"
                    className={`w-full rounded-xl border px-3 py-2 text-left text-sm ${
                      selectedChatId === chat.id
                        ? "border-[#f7c744]/40 bg-[#f7c744]/10 text-[#fff2a2]"
                        : "border-white/10 bg-white/5 text-[#d9c995]"
                    }`}
                    onClick={() => {
                      setSelectedChatId(chat.id);
                      setTo(chat.id);
                    }}
                  >
                    {chat.name} ({chat.unreadCount})
                  </button>
                ))}
              </div>
            ) : (
              <p className="text-sm text-[#d9c995]">Belum ada chat.</p>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
        <Card>
          <CardHeader>
            <CardTitle>Messages</CardTitle>
          </CardHeader>
          <CardContent>
            {selectedChatId && messages.data?.items ? (
              <div className="max-h-[420px] space-y-2 overflow-auto">
                {messages.data.items.map((item) => (
                  <div
                    key={item.id}
                    className={`max-w-[80%] rounded-xl px-3 py-2 text-sm ${
                      item.fromMe ? "ml-auto bg-[#f7c744]/20 text-[#fff2a2]" : "mr-auto bg-white/10 text-[#d9c995]"
                    }`}
                  >
                    <p>{item.body}</p>
                    <p className="mt-1 text-xs opacity-70">
                      {item.timestamp ? new Date(item.timestamp * 1000).toLocaleString() : "-"}
                    </p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-[#d9c995]">Pilih chat dulu.</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Send Message</CardTitle>
            <CardDescription>To bisa nomor `628xxx` atau chat id `@c.us`.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            <input
              value={to}
              onChange={(event) => setTo(event.target.value)}
              placeholder="628xxxxxx"
              className="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
            />
            <textarea
              value={message}
              onChange={(event) => setMessage(event.target.value)}
              rows={6}
              placeholder="Tulis pesan..."
              className="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
            />
            <Button type="button" onClick={onSend}>Send</Button>
            <p className="text-xs text-[#d9c995]">{sendResult}</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}


import * as React from "react";
import { QueryClient, QueryClientProvider, useQuery } from "@tanstack/react-query";
import { BarChart3, BookOpenCheck, KanbanSquare, LayoutDashboard, LogOut, MessageSquareQuote, Users } from "lucide-react";
import { Toaster } from "sonner";
import { Route, Router, Switch, useLocation } from "wouter";

import { boot, fetchJson } from "@/lib/api";
import type { MetaResponse } from "@/types";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { LoadingState } from "@/components/app/shared";
import { DashboardPage } from "@/pages/DashboardPage";
import { ChatReviewsPage } from "@/pages/ChatReviewsPage";
import { KnowledgeQueuePage } from "@/pages/KnowledgeQueuePage";
import { PerformancePage } from "@/pages/PerformancePage";
import { PipelinePage } from "@/pages/PipelinePage";
import { ProspectsPage } from "@/pages/ProspectsPage";

const queryClient = new QueryClient();

const navIcons = {
  Dashboard: LayoutDashboard,
  Prospek: Users,
  Pipeline: KanbanSquare,
  Kinerja: BarChart3,
  "Tinjauan Obrolan": MessageSquareQuote,
  "Antrian Pengetahuan": BookOpenCheck,
};

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <Router base={boot.basePath}>
        <AppShell />
      </Router>
      <Toaster theme="dark" richColors position="top-right" />
    </QueryClientProvider>
  );
}

function AppShell() {
  const meta = useQuery({
    queryKey: ["meta"],
    queryFn: () => fetchJson<MetaResponse>("/react-api/meta"),
  });
  const [location] = useLocation();

  if (meta.isLoading) return <LoadingState label="Memuat shell React..." />;

  return (
    <div className="min-h-screen bg-transparent px-4 py-4 text-white md:px-6">
      <div className="mx-auto grid min-h-[calc(100vh-2rem)] max-w-[1600px] gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside className="rounded-[28px] border border-white/10 bg-[rgba(11,14,19,0.88)] p-4 backdrop-blur-xl">
          <div className="rounded-[24px] border border-white/10 bg-white/5 p-4">
            <img src="/brand/logo-word.svg" alt="SGB Sales Command Center" className="h-9 w-auto" />
            <h1 className="mt-4 text-xl font-semibold tracking-tight">{boot.user.name}</h1>
            <p className="mt-1 text-sm text-slate-400">{boot.user.roleLabel}</p>
          </div>

          <nav className="mt-6 space-y-2">
            {meta.data?.navigation.map((item) => {
              const Icon = navIcons[item.label as keyof typeof navIcons] ?? LayoutDashboard;
              const isActive = location === item.href || location.startsWith(`${item.href}?`);

              return (
                <a
                  key={item.href}
                  href={item.href}
                  className={`flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm transition ${
                    isActive
                      ? "border-white/10 bg-white/10 text-white"
                      : "border-transparent text-slate-400 hover:border-white/10 hover:bg-white/5 hover:text-white"
                  }`}
                >
                  <Icon className="h-4 w-4" />
                  {item.label}
                </a>
              );
            })}
          </nav>

          <div className="mt-8 rounded-[24px] border border-white/10 bg-white/5 p-4">
            <p className="text-xs uppercase tracking-[0.24em] text-slate-500">Frontend Stack</p>
            <p className="mt-2 text-sm leading-6 text-slate-300">
              React, TypeScript, Wouter, TanStack Query, dan komponen shadcn-style di atas backend Laravel yang sama.
            </p>
          </div>
        </aside>

        <div className="space-y-4">
          <header className="flex flex-col gap-4 rounded-[28px] border border-white/10 bg-[rgba(10,13,18,0.72)] px-5 py-4 backdrop-blur-xl md:flex-row md:items-center">
            <div>
              <div className="flex items-center gap-3">
                <img src="/brand/logo-mark.svg" alt="SGB" className="h-9 w-9 rounded-2xl" />
                <p className="text-xs uppercase tracking-[0.24em] text-slate-500">Sales Command Center</p>
              </div>
              <h2 className="text-2xl font-semibold tracking-tight">Operasional CRM, review obrolan, dan knowledge loop dalam satu jalur React</h2>
            </div>
            <div className="ml-auto flex items-center gap-3">
              <div className="hidden items-center gap-3 rounded-full border border-white/10 bg-white/5 px-2 py-1 md:flex">
                <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-sm font-bold text-slate-950">
                  {boot.user.initials}
                </span>
                <div className="pr-3">
                  <p className="text-sm font-medium">{boot.user.name}</p>
                  <p className="text-xs text-slate-500">{boot.user.roleLabel}</p>
                </div>
              </div>
              <form method="post" action={boot.routes.logout}>
                <input type="hidden" name="_token" value={boot.csrfToken} />
                <Button type="submit" variant="secondary" size="sm">
                  <LogOut className="h-4 w-4" />
                  Logout
                </Button>
              </form>
            </div>
          </header>

          <Switch>
            <Route path="/dashboard" component={DashboardPage} />
            <Route path="/prospects" component={ProspectsPage} />
            <Route path="/pipeline" component={PipelinePage} />
            <Route path="/kinerja-penjualan" component={PerformancePage} />
            <Route path="/chat-reviews" component={ChatReviewsPage} />
            <Route path="/knowledge-queue" component={KnowledgeQueuePage} />
            <Route component={RedirectToDashboard} />
          </Switch>
        </div>
      </div>
    </div>
  );
}

function RedirectToDashboard() {
  const [, setLocation] = useLocation();

  React.useEffect(() => {
    setLocation("/dashboard");
  }, [setLocation]);

  return (
    <Card>
      <CardContent className="py-16 text-center text-slate-400">Mengalihkan ke dashboard...</CardContent>
    </Card>
  );
}

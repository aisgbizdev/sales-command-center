import * as React from "react";
import {
    QueryClient,
    QueryClientProvider,
    useQuery,
} from "@tanstack/react-query";
import {
    BarChart3,
    BookOpenCheck,
    Command,
    KanbanSquare,
    LayoutDashboard,
    ListTodo,
    LogOut,
    MessageSquareQuote,
    UserCog,
    Users,
} from "lucide-react";
import { Toaster } from "sonner";
import { Route, Router, Switch, useLocation } from "wouter";

import { boot, fetchJson } from "@/lib/api";
import type { MetaResponse } from "@/types";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { LoadingState } from "@/components/app/shared";
import { DashboardPage } from "@/pages/DashboardPage";
import { ChatReviewsPage } from "@/pages/ChatReviewsPage";
import {
    ChatReviewCreatePage,
    ChatReviewEditPage,
} from "@/pages/ChatReviewFormPage";
import { ChatReviewDetailPage } from "@/pages/ChatReviewDetailPage";
import { KnowledgeQueuePage } from "@/pages/KnowledgeQueuePage";
import { ManagerInsightsPage } from "@/pages/ManagerInsightsPage";
import { PerformancePage } from "@/pages/PerformancePage";
import { PipelinePage } from "@/pages/PipelinePage";
import { QueuePage } from "@/pages/QueuePage";
import { ProspectsPage } from "@/pages/ProspectsPage";
import { ProspectCreatePage, ProspectEditPage } from "@/pages/ProspectFormPage";
import { ProspectDetailPage } from "@/pages/ProspectDetailPage";
import { UsersPage } from "@/pages/UsersPage";

const queryClient = new QueryClient();

const navIcons = {
    Dashboard: LayoutDashboard,
    Prospek: Users,
    Pipeline: KanbanSquare,
    Queue: ListTodo,
    "Command Center": Command,
    Kinerja: BarChart3,
    "Tinjauan Obrolan": MessageSquareQuote,
    "Antrian Pengetahuan": BookOpenCheck,
    "Manajemen User": UserCog,
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
    const [sidebarOpen, setSidebarOpen] = React.useState(false);

    React.useEffect(() => {
        if (!sidebarOpen) return;

        const previousOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = "hidden";

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === "Escape") setSidebarOpen(false);
        };
        window.addEventListener("keydown", onKeyDown);

        return () => {
            window.removeEventListener("keydown", onKeyDown);
            document.documentElement.style.overflow = previousOverflow;
        };
    }, [sidebarOpen]);

    const navigation = meta.data?.navigation ?? [];
    const closeSidebar = () => setSidebarOpen(false);

    const SidebarContent = ({ onNavigate }: { onNavigate?: () => void }) => (
        <>
            <div className="rounded-[24px] border border-[#f7c744]/20 bg-[#f7c744]/10 p-4">
                <img
                    src="/brand/Logo%20SG-WEB111.png"
                    alt="SGB Sales Command Center"
                    className="h-9 w-auto"
                />
                <h1 className="mt-4 text-xl font-semibold tracking-tight text-[#ffe37b]">
                    {boot.user.name}
                </h1>
                <p className="mt-1 text-sm text-[#d9c995]">
                    {boot.user.roleLabel}
                </p>
            </div>

            <nav className="mt-6 space-y-2">
                {navigation.map((item) => {
                    const Icon =
                        navIcons[item.label as keyof typeof navIcons] ??
                        LayoutDashboard;
                    const isActive =
                        location === item.href ||
                        location.startsWith(`${item.href}?`);

                    return (
                        <a
                            key={item.href}
                            href={item.href}
                            onClick={onNavigate}
                            className={`flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm transition ${
                                isActive
                                    ? "border-[#f7c744]/30 bg-[#f7c744]/15 text-[#ffe37b] shadow-[0_12px_26px_rgba(247,199,68,0.08)]"
                                    : "border-transparent text-[#d9c995] hover:border-[#f7c744]/20 hover:bg-[#f7c744]/10 hover:text-[#ffe37b]"
                            }`}
                        >
                            <Icon className="h-4 w-4" />
                            {item.label}
                        </a>
                    );
                })}
            </nav>
        </>
    );

    if (meta.isLoading) return <LoadingState label="Memuat shell React..." />;

    return (
        <div className="min-h-screen bg-transparent px-4 py-4 text-[#fff2a2] md:px-6">
            <div className="grid min-h-[calc(100vh-2rem)] gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
                <div className="xl:hidden">
                    <div
                        className={`fixed inset-0 z-40 bg-black/60 backdrop-blur-sm transition-opacity ${
                            sidebarOpen
                                ? "opacity-100"
                                : "pointer-events-none opacity-0"
                        }`}
                        onClick={closeSidebar}
                    />
                    <aside
                        className={`fixed inset-y-0 left-0 z-50 w-[82vw] max-w-[360px] -translate-x-full overflow-y-auto border-r border-[#f7c744]/20 bg-[rgba(9,9,7,0.94)] p-4 backdrop-blur-xl transition-transform duration-300 ${
                            sidebarOpen ? "translate-x-0" : ""
                        }`}
                        aria-hidden={!sidebarOpen}
                    >
                        <SidebarContent onNavigate={closeSidebar} />
                    </aside>
                </div>

                <aside className="hidden rounded-[28px] border border-[#f7c744]/20 bg-[rgba(9,9,7,0.84)] p-4 backdrop-blur-xl xl:sticky xl:top-4 xl:block xl:h-[calc(100vh-2rem)]">
                    <SidebarContent />
                </aside>

                <div className="min-w-0 space-y-4">
                    <header className="rounded-[28px] border border-[#f7c744]/20 bg-[rgba(9,9,7,0.68)] px-5 py-4 backdrop-blur-xl xl:sticky xl:top-4 xl:z-20">
                        <div className="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-[#f7c744]/25 bg-[#f7c744]/10 text-[#ffe37b] xl:hidden"
                                aria-label="Buka menu"
                                onClick={() => setSidebarOpen(true)}
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    className="h-5 w-5"
                                    fill="none"
                                >
                                    <path
                                        d="M4 7h16M4 12h16M4 17h16"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                    />
                                </svg>
                            </button>

                            <div className="hidden items-center gap-3 md:flex">
                                <img
                                    src="/brand/Logo%20SG-WEB111.png"
                                    alt="SGB"
                                    className="h-9 w-9 rounded-2xl"
                                />
                                <p className="text-xs uppercase tracking-[0.24em] text-[#d9c995]">
                                    Sales Command Center
                                </p>
                            </div>

                            {/* <div className="relative hidden min-w-[260px] flex-1 xl:block">
                                <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#d9c995]/70">
                                    <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none">
                                        <path
                                            d="m21 21-4.3-4.3M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14z"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                        />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    placeholder="Search or type command..."
                                    className="h-11 w-full rounded-2xl border border-white/10 bg-white/[0.04] pl-12 pr-24 text-sm text-[#fff2a2] placeholder:text-[#d9c995]/60 focus:border-white/20 focus:outline-none focus:ring-4 focus:ring-white/5"
                                />
                                <kbd className="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl border border-white/10 bg-white/[0.06] px-2 py-1 text-xs text-[#d9c995]">
                                    Ctrl K
                                </kbd>
                            </div> */}

                            <div className="ml-auto flex items-center gap-3">
                                <div className="hidden items-center gap-3 rounded-full border border-[#f7c744]/20 bg-[#f7c744]/10 px-2 py-1 md:flex">
                                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[linear-gradient(135deg,#fff2a2,#f7c744_46%,#b87500)] text-sm font-bold text-[#2a1600]">
                                        {boot.user.initials}
                                    </span>
                                    <div className="pr-3">
                                        <p className="text-sm font-medium text-[#fff2a2]">
                                            {boot.user.name}
                                        </p>
                                        <p className="text-xs text-[#d9c995]">
                                            {boot.user.roleLabel}
                                        </p>
                                    </div>
                                </div>
                                <form method="post" action={boot.routes.logout}>
                                    <input
                                        type="hidden"
                                        name="_token"
                                        value={boot.csrfToken}
                                    />
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        size="sm"
                                    >
                                        <LogOut className="h-4 w-4" />
                                        <span className="hidden sm:inline">
                                            Logout
                                        </span>
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </header>

                    <Switch>
                        <Route path="/dashboard" component={DashboardPage} />
                        <Route
                            path="/prospects/create"
                            component={ProspectCreatePage}
                        />
                        <Route
                            path="/prospects/:id/edit"
                            component={ProspectEditPage}
                        />
                        <Route
                            path="/prospects/:id"
                            component={ProspectDetailPage}
                        />
                        <Route path="/prospects" component={ProspectsPage} />
                        <Route path="/pipeline" component={PipelinePage} />
                        <Route path="/queue" component={QueuePage} />
                        <Route
                            path="/manager-insights"
                            component={ManagerInsightsPage}
                        />
                        <Route
                            path="/kinerja-penjualan"
                            component={PerformancePage}
                        />
                        <Route
                            path="/chat-reviews/create"
                            component={ChatReviewCreatePage}
                        />
                        <Route
                            path="/chat-reviews/:id/edit"
                            component={ChatReviewEditPage}
                        />
                        <Route
                            path="/chat-reviews/:id"
                            component={ChatReviewDetailPage}
                        />
                        <Route
                            path="/chat-reviews"
                            component={ChatReviewsPage}
                        />
                        <Route
                            path="/knowledge-queue"
                            component={KnowledgeQueuePage}
                        />
                        <Route path="/users" component={UsersPage} />
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
            <CardContent className="py-16 text-center text-[#d9c995]">
                Mengalihkan ke dashboard...
            </CardContent>
        </Card>
    );
}

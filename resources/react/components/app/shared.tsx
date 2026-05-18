import * as React from "react";
import { LoaderCircle } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { formatNumber } from "@/lib/utils";
import type { Option } from "@/types";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

type BadgeVariant = "default" | "info" | "success" | "orange" | "warn" | "danger";

export function statusVariant(status: string): BadgeVariant {
  if (status === "penutupan") return "success";
  if (status === "hilang") return "danger";
  if (status === "tindak_lanjut" || status === "sedang_berjalan") return "warn";
  return "info";
}

export function followUpVariant(state: string): BadgeVariant {
  if (state === "overdue") return "danger";
  if (state === "today") return "orange";
  if (state === "soon") return "warn";
  if (state === "healthy") return "success";
  return "default";
}

export function followUpLabel(state: string, overdueDays = 0) {
  if (state === "overdue") return overdueDays > 0 ? `Overdue ${overdueDays} hari` : "Overdue";
  if (state === "today") return "Due Today";
  if (state === "soon") return "Due Soon";
  if (state === "healthy") return "Healthy";
  return "No Follow Up";
}

export function priorityVariant(level: string): BadgeVariant {
  if (level === "critical") return "danger";
  if (level === "high") return "orange";
  if (level === "medium") return "warn";
  return "success";
}

export function healthVariant(state: string): BadgeVariant {
  if (state === "critical") return "danger";
  if (state === "warning") return "warn";
  return "success";
}

export function getQueryString(location: string) {
  if (typeof window === "undefined") return "";
  const url = toUrl(location);
  return url.search;
}

export function buildQuery(values: Record<string, string>) {
  const params = new URLSearchParams();
  Object.entries(values).forEach(([key, value]) => {
    if (value) params.set(key, value);
  });
  return params.toString();
}

export function movePage(location: string, setLocation: (path: string) => void, page: number) {
  const url = toUrl(location);
  url.searchParams.set("page", String(page));
  setLocation(`${url.pathname}${url.search}`);
}

function toUrl(location: string) {
  const base = window.location.origin;
  if (location.includes("?")) return new URL(location, base);
  return new URL(`${location}${window.location.search}`, base);
}

export function MetricCard({ label, value, note }: { label: string; value: number; note: string }) {
  return (
    <Card>
      <CardContent className="space-y-3">
        <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">{label}</p>
        <p className="text-4xl font-semibold tracking-[-0.05em]">{formatNumber(value)}</p>
        <p className="text-sm leading-6 text-[#d9c995]">{note}</p>
      </CardContent>
    </Card>
  );
}

export function MiniMetric({
  label,
  value,
  variant = "info",
}: {
  label: string;
  value: number;
  variant?: "info" | "orange" | "warn" | "danger";
}) {
  return (
    <div className="rounded-[18px] border border-white/10 bg-white/5 p-4">
      <p className="text-xs uppercase tracking-[0.18em] text-[#d9c995]/70">{label}</p>
      <div className="mt-3 flex items-center gap-3">
        <p className="text-2xl font-semibold tracking-[-0.04em]">{formatNumber(value)}</p>
        <Badge variant={variant}>{label}</Badge>
      </div>
    </div>
  );
}

export function DataTable({
  headers,
  rows,
  emptyMessage,
}: {
  headers: string[];
  rows: React.ReactNode[][];
  emptyMessage: string;
}) {
  return (
    <div className="overflow-x-auto rounded-[22px] border border-white/10">
      <table className="min-w-full border-collapse bg-white/[0.02]">
        <thead>
          <tr className="bg-white/[0.03]">
            {headers.map((header) => (
              <th key={header} className="px-4 py-3 text-left text-[11px] uppercase tracking-[0.18em] text-[#d9c995]">
                {header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 ? (
            <tr>
              <td colSpan={headers.length} className="px-4 py-10 text-center text-sm text-[#d9c995]/70">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            rows.map((row, index) => (
              <tr key={index} className="border-t border-white/10 align-top text-sm text-[#d9c995] odd:bg-transparent even:bg-white/[0.02]">
                {row.map((cell, cellIndex) => (
                  <td key={cellIndex} className="px-4 py-4">
                    {cell}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}

export function LoadingState({ label }: { label: string }) {
  return (
    <Card>
      <CardContent className="flex min-h-[260px] items-center justify-center gap-3 text-[#d9c995]">
        <LoaderCircle className="h-5 w-5 animate-spin" />
        {label}
      </CardContent>
    </Card>
  );
}

export function ErrorState() {
  return (
    <Card>
      <CardContent className="min-h-[260px] space-y-3 py-14 text-center">
        <p className="text-xl font-semibold text-[#fff2a2]">Gagal memuat data React preview.</p>
        <p className="text-sm text-[#d9c995]">Cek session login atau endpoint JSON backend.</p>
      </CardContent>
    </Card>
  );
}

export function NativeSelect({
  value,
  onChange,
  options,
  placeholder,
  className,
}: {
  value: string;
  onChange: (value: string) => void;
  options: Option[];
  placeholder: string;
  className?: string;
}) {
  return (
    <Select value={value || "all"} onValueChange={(next) => onChange(next === "all" ? "" : next)}>
      <SelectTrigger className={className}>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">{placeholder}</SelectItem>
        {options.map((option) => (
          <SelectItem key={option.value} value={option.value}>
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}

export function OverlayModal({
  open,
  title,
  subtitle = "Filter",
  onClose,
  children,
  maxWidthClass = "max-w-[560px]",
}: {
  open: boolean;
  title: string;
  subtitle?: string;
  onClose: () => void;
  children: React.ReactNode;
  maxWidthClass?: string;
}) {
  React.useEffect(() => {
    if (!open) return;

    const previousOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = "hidden";

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
    };
    window.addEventListener("keydown", onKeyDown);

    return () => {
      window.removeEventListener("keydown", onKeyDown);
      document.documentElement.style.overflow = previousOverflow;
    };
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[60]">
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />
      <div className="absolute inset-0 flex items-end justify-center p-4 sm:items-center">
        <div
          className={`w-full ${maxWidthClass} rounded-[28px] border border-white/10 bg-[rgba(11,14,19,0.96)] p-4 shadow-[0_30px_80px_rgba(0,0,0,0.55)] backdrop-blur-xl`}
        >
          <div className="flex items-center gap-3">
            <div className="min-w-0">
              <p className="text-xs uppercase tracking-[0.2em] text-[#d9c995]/70">{subtitle}</p>
              <p className="truncate text-lg font-semibold text-[#fff2a2]">{title}</p>
            </div>
            <Button type="button" variant="secondary" size="sm" className="ml-auto" onClick={onClose}>
              Tutup
            </Button>
          </div>
          <div className="mt-4 max-h-[calc(100vh-10rem)] overflow-y-auto pr-1">
            {children}
          </div>
        </div>
      </div>
    </div>
  );
}

import * as React from "react";
import { LoaderCircle } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { formatNumber } from "@/lib/utils";
import type { Option } from "@/types";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export function statusVariant(status: string): "default" | "info" | "success" | "warn" | "danger" {
  if (status === "penutupan") return "success";
  if (status === "hilang") return "danger";
  if (status === "tindak_lanjut" || status === "sedang_berjalan") return "warn";
  return "info";
}

export function getQueryString(location: string) {
  const url = new URL(location, window.location.origin);
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
  const url = new URL(location, window.location.origin);
  url.searchParams.set("page", String(page));
  setLocation(`${url.pathname}${url.search}`);
}

export function MetricCard({ label, value, note }: { label: string; value: number; note: string }) {
  return (
    <Card>
      <CardContent className="space-y-3">
        <p className="text-xs uppercase tracking-[0.18em] text-slate-500">{label}</p>
        <p className="text-4xl font-semibold tracking-[-0.05em]">{formatNumber(value)}</p>
        <p className="text-sm leading-6 text-slate-400">{note}</p>
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
  variant?: "info" | "warn" | "danger";
}) {
  return (
    <div className="rounded-[18px] border border-white/10 bg-white/5 p-4">
      <p className="text-xs uppercase tracking-[0.18em] text-slate-500">{label}</p>
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
              <th key={header} className="px-4 py-3 text-left text-[11px] uppercase tracking-[0.18em] text-slate-400">
                {header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 ? (
            <tr>
              <td colSpan={headers.length} className="px-4 py-10 text-center text-sm text-slate-500">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            rows.map((row, index) => (
              <tr key={index} className="border-t border-white/10 align-top text-sm text-slate-200 odd:bg-transparent even:bg-white/[0.02]">
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
      <CardContent className="flex min-h-[260px] items-center justify-center gap-3 text-slate-400">
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
        <p className="text-xl font-semibold text-white">Gagal memuat data React preview.</p>
        <p className="text-sm text-slate-400">Cek session login atau endpoint JSON backend.</p>
      </CardContent>
    </Card>
  );
}

export function NativeSelect({
  value,
  onChange,
  options,
  placeholder,
}: {
  value: string;
  onChange: (value: string) => void;
  options: Option[];
  placeholder: string;
}) {
  return (
    <Select value={value || "all"} onValueChange={(next) => onChange(next === "all" ? "" : next)}>
      <SelectTrigger>
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

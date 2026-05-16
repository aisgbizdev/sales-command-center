import * as React from "react";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, Save } from "lucide-react";
import { useLocation } from "wouter";

import { boot, fetchJson } from "@/lib/api";
import type { ProspectDetailResponse, ProspectFormResponse } from "@/types";
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
    <label className="grid gap-2 text-sm text-[#d9c995]">
      {label}
      <select
        name={name}
        defaultValue={defaultValue ?? ""}
        required={required}
        className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
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

export function ProspectCreatePage() {
  return <ProspectForm mode="create" />;
}

export function ProspectEditPage({ params }: { params: { id: string } }) {
  return <ProspectForm mode="edit" id={params.id} />;
}

function ProspectForm({ mode, id }: { mode: "create" | "edit"; id?: string }) {
  const [, setLocation] = useLocation();

  const formData = useQuery({
    queryKey: ["prospects", "form"],
    queryFn: () => fetchJson<ProspectFormResponse>("/react-api/prospects/form"),
  });

  const detail = useQuery({
    queryKey: ["prospect", id],
    enabled: mode === "edit" && Boolean(id),
    queryFn: () => fetchJson<ProspectDetailResponse>(`/react-api/prospects/${id}`),
  });

  if (formData.isLoading || (mode === "edit" && detail.isLoading)) {
    return <LoadingState label="Memuat form prospek..." />;
  }

  if (formData.isError || !formData.data || (mode === "edit" && (detail.isError || !detail.data))) {
    return <ErrorState />;
  }

  const data = formData.data;
  const current = detail.data?.prospect;
  const editDetail = detail.data;

  const action = mode === "create" ? "/prospects" : `/prospects/${current?.id}`;

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <CardTitle className="text-3xl">{mode === "create" ? "Input Prospek Baru" : "Edit Prospek"}</CardTitle>
            <CardDescription>{mode === "create" ? "Tambahkan prospek baru ke sistem." : `Update data untuk ${current?.prospectCode}.`}</CardDescription>
          </div>
          <Button type="button" variant="secondary" onClick={() => setLocation("/prospects")}>
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
            <CardTitle>Data Utama Prospek</CardTitle>
            <CardDescription>Identitas dan info dasar prospek.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <label className="grid gap-2 text-sm text-[#d9c995] sm:col-span-2 lg:col-span-3">
              Nama Prospek
              <input
                name="name"
                defaultValue={current?.name ?? ""}
                required
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Contoh: Budi Santoso"
              />
            </label>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Perusahaan
              <input
                name="company"
                defaultValue={current?.company === "Belum ada nama perusahaan" ? "" : (current?.company ?? "")}
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Opsional"
              />
            </label>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Nomor HP / WhatsApp
              <input
                name="phone"
                defaultValue={editDetail?.prospect.phone ?? ""}
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Contoh: 0812xxxxxx"
              />
            </label>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Email
              <input
                type="email"
                name="email"
                defaultValue={editDetail?.prospect.email ?? ""}
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Opsional"
              />
            </label>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Klasifikasi Penjualan</CardTitle>
            <CardDescription>Label dan sinyal AI untuk filter dashboard.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <SelectField name="source" label="Sumber" options={data.sources} defaultValue={editDetail?.prospect.source ?? ""} placeholder="-" />
            <SelectField name="account_category" label="Kategori Akun" options={data.accountCategories} defaultValue={current?.accountCategory ?? "reguler"} required />
            <SelectField name="status" label="Status" options={data.statuses} defaultValue={current?.status ?? "baru"} required />
            <SelectField name="gpt_mode" label="Mode GPT" options={data.gptModes} defaultValue={editDetail?.prospect.gptMode ?? ""} placeholder="Ikuti kategori akun" />
            <SelectField name="user_temperature" label="User Temperature" options={data.userTemperatures} defaultValue={editDetail?.prospect.userTemperature ?? ""} placeholder="-" />
            <SelectField name="dominant_emotion" label="Emosi Dominan" options={data.dominantEmotions} defaultValue={editDetail?.prospect.dominantEmotion ?? ""} placeholder="-" />
            <label className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#d9c995]">
              <input type="checkbox" name="bridge_candidate" value="1" defaultChecked={editDetail?.prospect.bridgeCandidate ?? false} />
              Kandidat Bridge
            </label>
            <SelectField name="bridge_status" label="Bridge Status" options={data.bridgeStatuses} defaultValue={editDetail?.prospect.bridgeStatus ?? ""} placeholder="-" />
            <SelectField name="lost_reason" label="Lost Reason" options={data.lostReasons} defaultValue={editDetail?.prospect.lostReason ?? ""} placeholder="-" />
            <label className="grid gap-2 text-sm text-[#d9c995] sm:col-span-2 lg:col-span-3">
              Keberatan Utama
              <textarea
                name="main_objection"
                defaultValue={editDetail?.prospect.mainObjection ?? ""}
                className="min-h-[92px] rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
                placeholder="Catat keberatan inti prospek"
              />
            </label>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Prioritas dan Follow Up</CardTitle>
            <CardDescription>Dipakai untuk reminder dan deteksi overdue.</CardDescription>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Prioritas
              <select
                name="priority"
                defaultValue={String(editDetail?.prospect.priority ?? 2)}
                required
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
              >
                <option value="1">Tinggi</option>
                <option value="2">Sedang</option>
                <option value="3">Rendah</option>
              </select>
            </label>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Estimasi Nilai Potensi
              <input
                type="number"
                min="0"
                step="0.01"
                name="estimation_value"
                defaultValue={String(editDetail?.prospect.estimationValue ?? 0)}
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            <label className="grid gap-2 text-sm text-[#d9c995]">
              Tanggal Follow Up Berikutnya
              <input
                type="date"
                name="next_follow_up_date"
                defaultValue={editDetail?.prospect.nextFollowUpDate ?? ""}
                className="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-[#fff2a2] outline-none focus:border-white/20 focus:ring-4 focus:ring-white/5"
              />
            </label>
            {data.canAssignOwner ? (
              <SelectField name="owner_id" label="Owner (Penjualan)" options={data.salesUsers} defaultValue={editDetail?.prospect.ownerId ?? ""} placeholder="Pilih owner" required />
            ) : null}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Catatan Internal</CardTitle>
            <CardDescription>Ringkasan tambahan untuk tim internal.</CardDescription>
          </CardHeader>
          <CardContent>
            <textarea
              name="notes"
              defaultValue={current?.notes ?? ""}
              className="min-h-[120px] w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-[#fff2a2] outline-none placeholder:text-[#d9c995]/60 focus:border-white/20 focus:ring-4 focus:ring-white/5"
              placeholder="Catatan singkat untuk tim internal"
            />
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

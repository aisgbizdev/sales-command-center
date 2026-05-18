import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const badgeVariants = cva(
  "inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]",
  {
    variants: {
      variant: {
        default: "border-[#f7c744]/20 bg-[#f7c744]/10 text-[#d9c995]",
        info: "border-[#ffe37b]/25 bg-[#f7c744]/15 text-[#fff2a2]",
        success: "border-emerald-300/20 bg-emerald-500/15 text-emerald-200",
        orange: "border-orange-300/25 bg-orange-500/15 text-orange-200",
        warn: "border-[#ffe37b]/30 bg-[#f7c744]/20 text-[#ffe37b]",
        danger: "border-red-300/20 bg-red-500/15 text-red-200",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

export interface BadgeProps extends React.HTMLAttributes<HTMLDivElement>, VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return <div className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge };

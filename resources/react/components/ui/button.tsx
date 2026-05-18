import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-2xl text-sm font-semibold transition-all disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default:
          "border border-[#ffe37b]/40 bg-[linear-gradient(135deg,#fff2a2_0%,#f7c744_42%,#b87500_100%)] text-[#2a1600] shadow-[0_16px_36px_rgba(184,117,0,0.22)] hover:bg-[linear-gradient(135deg,#fff8bd_0%,#ffd95e_45%,#c88400_100%)] hover:shadow-[0_18px_42px_rgba(247,199,68,0.24)]",
        secondary:
          "border border-[#f7c744]/30 bg-[#f7c744]/10 text-[#ffe37b] hover:border-[#ffe37b]/40 hover:bg-[#f7c744]/15 hover:text-[#fff2a2]",
        ghost:
          "text-[#d9c995] hover:bg-[#f7c744]/10 hover:text-[#ffe37b]",
        danger:
          "border border-red-300/20 bg-red-500/15 text-red-200 hover:bg-red-500/20",
      },
      size: {
        default: "h-11 px-4 py-2",
        sm: "h-9 rounded-xl px-3",
        lg: "h-12 rounded-2xl px-5",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, ...props }, ref) => (
    <button
      className={cn(buttonVariants({ variant, size, className }))}
      ref={ref}
      {...props}
    />
  )
);
Button.displayName = "Button";

export { Button, buttonVariants };

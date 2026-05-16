import * as React from "react";

import { cn } from "@/lib/utils";

const Input = React.forwardRef<HTMLInputElement, React.ComponentProps<"input">>(
  ({ className, ...props }, ref) => (
    <input
      ref={ref}
      className={cn(
        "flex h-11 w-full rounded-2xl border border-[#f7c744]/25 bg-[#f7c744]/10 px-4 py-2 text-sm text-[#fff2a2] outline-none transition placeholder:text-[#d9c995]/60 focus:border-[#ffe37b]/50 focus:ring-4 focus:ring-[#f7c744]/15",
        className
      )}
      {...props}
    />
  )
);
Input.displayName = "Input";

export { Input };

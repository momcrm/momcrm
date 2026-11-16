import * as React from "react"

import { cn } from "@/lib/utils"

function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        "file:text-foreground selection:bg-primary selection:text-primary-foreground flex h-control-md w-full min-w-0 rounded-md border px-2.5 py-1 text-sm file:inline-flex file:h-6 file:border-0 file:bg-transparent file:text-ui file:font-medium border-border-strong bg-card shadow-none transition-[color,border-color,box-shadow] duration-150 outline-none placeholder:text-muted-foreground hover:border-[color-mix(in_srgb,var(--border-strong)_60%,var(--muted-foreground))] focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground aria-invalid:border-destructive aria-invalid:ring-[3px] aria-invalid:ring-destructive/10",
        className
      )}
      {...props}
    />
  )
}

export { Input }

import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * Buttons on gr8r's scale: 30px by default, 26px small, 36px large, with
 * 12.5px medium text. "outline" and "secondary" are both gr8r's bordered
 * secondary button; gr8r has no filled grey button.
 */
const buttonVariants = cva(
  "inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-transparent text-ui font-medium select-none transition-[color,background-color,border-color,box-shadow,transform] duration-150 active:not-disabled:scale-[0.97] disabled:pointer-events-none disabled:opacity-45 disabled:shadow-none [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 [&_svg]:opacity-90 outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground shadow-[inset_0_-1px_0_rgb(0_0_0/0.12)] hover:bg-[color-mix(in_srgb,var(--primary)_88%,var(--foreground))] active:bg-[color-mix(in_srgb,var(--primary)_78%,var(--foreground))]",
        destructive:
          "bg-destructive text-white hover:bg-[color-mix(in_srgb,var(--destructive)_88%,#000)] focus-visible:ring-destructive/20 dark:text-background dark:focus-visible:ring-destructive/40",
        outline:
          "border-border-strong bg-card text-foreground shadow-xs hover:bg-muted active:bg-accent",
        secondary:
          "border-border-strong bg-card text-foreground shadow-xs hover:bg-muted active:bg-accent",
        ghost:
          "text-soft-foreground hover:bg-muted hover:text-foreground active:bg-accent",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        default: "h-control-md px-[11px]",
        sm: "h-control-sm px-2 text-xs",
        lg: "h-control-lg px-3.5 text-sm",
        icon: "size-control-md",
        "icon-sm": "size-control-sm",
        "icon-xs": "size-control-xs rounded-[4px]",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
